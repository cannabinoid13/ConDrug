<?php

namespace ConDrug;

class Plugin
{
    protected static ?Plugin $instance = null;

    protected AdminMenu $adminMenu;

    protected Assets $assets;

    protected Shortcodes $shortcodes;

    protected StripeService $stripeService;

    protected AccessManager $access;

    protected FormHandler $forms;

    protected WebhookHandler $webhooks;

    protected MemberRepository $members;

    protected bool $badgeConsumed = false;

    public static function boot(): Plugin
    {
        if (null === static::$instance) {
            static::$instance = new static();
            static::$instance->init();
        }

        return static::$instance;
    }

    protected function init(): void
    {
        $this->stripeService = new StripeService();
        $this->assets = new Assets();
        $this->access = new AccessManager();
        $this->forms = new FormHandler();
        $this->shortcodes = new Shortcodes($this->assets);
        $this->adminMenu = new AdminMenu($this->assets);
        $this->webhooks = new WebhookHandler($this->stripeService);
        $this->members = new MemberRepository();
        $this->members->ensureTableExists();

        register_activation_hook(CONDRUG_PLUGIN_FILE, [$this, 'onActivation']);

        add_action('init', [$this->assets, 'register']);
        add_action('init', [$this, 'registerShortcodes']);
        add_action('admin_menu', [$this->adminMenu, 'register']);
        add_action('admin_enqueue_scripts', [$this->assets, 'enqueueAdmin']);
        add_action('wp_enqueue_scripts', [$this->assets, 'enqueueFrontend']);

        add_filter('condrug_action_urls', [$this, 'overrideActionUrls'], 10, 2);

        $this->forms->hooks();
        $this->webhooks->hooks();

        add_action('user_register', [$this, 'handleUserRegister']);
        add_action('admin_init', [$this, 'ensureAdminMembers']);

        add_action('load-condrug_page_condrug-subscribers', [$this, 'resetMemberCount']);

        add_action('admin_post_condrug_reset_member_count', [$this, 'resetMemberCount']);

        add_action('wp_ajax_condrug_create_checkout', [$this, 'handleCheckoutAjax']);
        add_action('wp_ajax_nopriv_condrug_create_checkout', [$this, 'handleCheckoutAjax']);

        add_action('wp_ajax_condrug_openfda_query', [$this, 'handleOpenFdaAjax']);
        add_action('wp_ajax_nopriv_condrug_openfda_query', [$this, 'handleOpenFdaAjax']);
    }

    public function onActivation(): void
    {
        $this->members->createTable();
        $this->ensureAdminMembers();
    }

    public function handleUserRegister(int $userId): void
    {
        $user = get_userdata($userId);
        if (!$user) {
            return;
        }

        if (!in_array('administrator', (array) $user->roles, true)) {
            $user->set_role('subscriber');
        }

        $this->members->upsertMember($userId, 'free', ['source' => 'registration']);
        SettingsRepository::getInstance()->incrementNewMemberCount();
    }

    public function ensureAdminMembers(): void
    {
        $admins = get_users(['role' => 'administrator', 'fields' => ['ID']]);
        foreach ($admins as $admin) {
            $this->members->upsertMember($admin->ID, 'growth', ['auto_enrolled' => true]);
        }
    }

    public function registerShortcodes(): void
    {
        $this->shortcodes->register();
    }

    public function stripe(): StripeService
    {
        return $this->stripeService;
    }

    public function access(): AccessManager
    {
        return $this->access;
    }

    public function overrideActionUrls(array $urls, string $base): array
    {
        $urls['plan'] = add_query_arg('condrug_action', 'plan', $base);
        $urls['workspace'] = add_query_arg('condrug_action', 'workspace', $base);
        return $urls;
    }

    public function handleCheckoutAjax(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in to checkout.', 'condrug')], 403);
        }

        check_ajax_referer('condrug_checkout', 'nonce');

        $planId = sanitize_key($_POST['plan_id'] ?? '');
        $coupon = sanitize_text_field($_POST['coupon_code'] ?? '');
        $session = $this->stripeService->createCheckoutSession(get_current_user_id(), $planId, $coupon);

        if (is_wp_error($session)) {
            wp_send_json_error(['message' => $session->get_error_message()], 400);
        }

        if (!$session) {
            wp_send_json_error(['message' => __('Unable to create checkout session.', 'condrug')], 400);
        }

        $this->members->upsertMember(get_current_user_id(), 'paid', ['plan_id' => $planId]);

        wp_send_json_success([
            'checkout_url' => $session->url,
            'session_id' => $session->id,
            'publishable_key' => $this->stripeService->getPublishableKey(),
        ]);
    }

    public function resetMemberCount(): void
    {
        if ($this->badgeConsumed) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        SettingsRepository::getInstance()->resetNewMemberCount();
        $this->badgeConsumed = true;

        if (isset($_POST['action']) && 'condrug_reset_member_count' === $_POST['action']) {
            check_admin_referer('condrug_reset_member_count');
            wp_safe_redirect(add_query_arg('settings-updated', 'true', wp_get_referer()));
            exit;
        }
    }

    public function handleOpenFdaAjax(): void
    {
        check_ajax_referer('condrug_openfda', 'nonce');

        $query = sanitize_text_field($_POST['q'] ?? '');
        if (strlen($query) < 2) {
            wp_send_json_error(['message' => __('Lütfen en az 2 karakter girin.', 'condrug')], 400);
        }

        $search = sprintf('(%1$s OR %2$s)',
            sprintf('patient.drug.medicinalproduct.exact:"%s"', $query),
            sprintf('patient.drug.openfda.substance_name.exact:"%s"', $query)
        );

        $base = 'https://api.fda.gov/drug/event.json';
        $httpArgs = [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'ConDrug OpenFDA Client (+https://example.com)'
            ],
        ];

        $buildUrl = static function (array $params) use ($base): string {
            return add_query_arg($params, $base);
        };

        $fetch = static function (array $params) use ($httpArgs, $buildUrl) {
            $url = $buildUrl($params);
            $response = wp_remote_get($url, $httpArgs);
            if (is_wp_error($response)) {
                return $response;
            }
            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            if ($code < 200 || $code >= 300) {
                return new \WP_Error('openfda_http_error', 'OpenFDA HTTP error', [
                    'status' => $code,
                    'body' => $body,
                ]);
            }
            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new \WP_Error('openfda_parse_error', 'OpenFDA response parse error');
            }
            return $data;
        };

        // 1) Total reports
        $metaRes = $fetch([
            'search' => $search,
            'limit' => 1,
        ]);
        if (is_wp_error($metaRes)) {
            wp_send_json_error(['message' => __('OpenFDA bağlantı hatası.', 'condrug')], 502);
        }
        $totalReports = (int)($metaRes['meta']['results']['total'] ?? 0);

        // 2) Reactions top N
        $reactionsRes = $fetch([
            'search' => $search,
            'count' => 'patient.reaction.reactionmeddrapt.exact',
            'limit' => 15,
        ]);
        $reactions = [];
        if (!is_wp_error($reactionsRes)) {
            foreach (($reactionsRes['results'] ?? []) as $row) {
                if (!isset($row['term'], $row['count'])) {
                    continue;
                }
                $reactions[] = [
                    'term' => (string) $row['term'],
                    'count' => (int) $row['count'],
                ];
            }
        }

        // 3) Sex distribution
        $sexRes = $fetch([
            'search' => $search,
            'count' => 'patient.patientsex',
            'limit' => 10,
        ]);
        $sexes = [];
        if (!is_wp_error($sexRes)) {
            foreach (($sexRes['results'] ?? []) as $row) {
                $code = (string) ($row['term'] ?? '');
                $label = match ($code) {
                    '1' => __('Erkek', 'condrug'),
                    '2' => __('Kadın', 'condrug'),
                    default => __('Bilinmiyor', 'condrug'),
                };
                $sexes[] = [
                    'term' => $label,
                    'code' => $code,
                    'count' => (int) ($row['count'] ?? 0),
                ];
            }
        }

        // 4) Age distribution (raw ages, to be binned client-side)
        $agesRes = $fetch([
            'search' => $search,
            'count' => 'patient.patientage',
            'limit' => 200,
        ]);
        $ages = [];
        if (!is_wp_error($agesRes)) {
            foreach (($agesRes['results'] ?? []) as $row) {
                if (!isset($row['term'], $row['count'])) {
                    continue;
                }
                $age = (int) $row['term'];
                if ($age <= 0 || $age > 120) {
                    continue; // discard outliers
                }
                $ages[] = [
                    'age' => $age,
                    'count' => (int) $row['count'],
                ];
            }
        }

        // 5) Yearly trend
        $yearsRes = $fetch([
            'search' => $search,
            'count' => 'receivedate:1year',
            'limit' => 30,
        ]);
        $years = [];
        if (!is_wp_error($yearsRes)) {
            foreach (($yearsRes['results'] ?? []) as $row) {
                $time = (string) ($row['time'] ?? '');
                $year = substr($time, 0, 4);
                if (!ctype_digit($year)) {
                    continue;
                }
                $years[] = [
                    'year' => $year,
                    'count' => (int) ($row['count'] ?? 0),
                ];
            }
        }

        $response = [
            'query' => $query,
            'totals' => [
                'reports' => $totalReports,
                'uniqueReactions' => count($reactions),
            ],
            'reactions' => $reactions,
            'sexes' => $sexes,
            'ages' => $ages,
            'years' => $years,
        ];

        if ($totalReports === 0 && empty($reactions) && empty($sexes) && empty($ages) && empty($years)) {
            wp_send_json_error(['message' => __('Sonuç bulunamadı.', 'condrug')]);
        }

        wp_send_json_success($response);
    }
}
