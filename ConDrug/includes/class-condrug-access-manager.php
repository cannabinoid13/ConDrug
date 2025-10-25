<?php

namespace ConDrug;

class AccessManager
{
    protected array $templates = [
        'login' => 'access-login.php',
        'register' => 'access-register.php',
        'plan' => 'plan-selection.php',
        'workspace' => 'workspace.php',
    ];

    protected ?array $currentStatus = null;

    protected array $notice = [];

    public function resolve(): array
    {
        $this->notice = $this->parseNotice();

        if (is_user_logged_in()) {
            $this->currentStatus = $this->getUserAccessStatus(get_current_user_id());
        } else {
            $this->currentStatus = null;
        }

        $view = $this->determineView();

        return [
            'view' => $view,
            'template' => $this->getTemplatePath($view),
            'data' => $this->buildData($view),
            'meta' => [
                'urls' => $this->getActionUrls(),
                'access' => $this->currentStatus,
                'notice' => $this->notice,
            ],
        ];
    }

    protected function determineView(): string
    {
        $requested = $this->getRequestedAction();

        if (!is_user_logged_in()) {
            return $requested === 'register' ? 'register' : 'login';
        }

        $status = $this->currentStatus ?? ['has_plan' => false];
        if (empty($status['has_plan'])) {
            return 'plan';
        }

        return 'workspace';
    }

    protected function getRequestedAction(): string
    {
        if (!isset($_GET['condrug_action'])) {
            return 'login';
        }

        $action = sanitize_text_field(wp_unslash($_GET['condrug_action']));
        if (!in_array($action, ['login', 'register', 'plan', 'workspace'], true)) {
            return 'login';
        }

        return $action;
    }

    protected function getTemplatePath(string $view): string
    {
        $file = $this->templates[$view] ?? $this->templates['login'];
        return CONDRUG_PLUGIN_DIR . 'templates/' . $file;
    }

    protected function buildData(string $view): array
    {
        switch ($view) {
            case 'register':
                return [
                    'policy_links' => $this->getPolicyLinks(),
                    'security' => [
                        'email_verification' => true,
                        'mfa' => true,
                    ],
                    'prefill' => $this->getPrefillData(['first_name', 'last_name', 'user_email']),
                ];
            case 'login':
                return [
                    'prefill' => $this->getPrefillData(['log']),
                ];
            case 'plan':
                return [
                    'plans' => $this->getPlanOptions(),
                    'status' => $this->currentStatus,
                ];
            case 'workspace':
            default:
                return [];
        }
    }

    protected function parseNotice(): array
    {
        if (empty($_GET['condrug_notice'])) {
            return [];
        }

        $json = wp_unslash($_GET['condrug_notice']);
        $decoded = json_decode(rawurldecode($json), true);

        if (!is_array($decoded)) {
            return [];
        }

        $type = in_array($decoded['type'] ?? '', ['success', 'error', 'info'], true)
            ? $decoded['type']
            : 'info';

        $text = sanitize_text_field($decoded['text'] ?? '');

        return [
            'type' => $type,
            'text' => $text,
        ];
    }

    protected function getPrefillData(array $keys): array
    {
        $prefill = [];
        foreach ($keys as $key) {
            if (isset($_REQUEST[$key])) {
                $prefill[$key] = sanitize_text_field(wp_unslash($_REQUEST[$key]));
            }
        }
        return $prefill;
    }

    protected function getPolicyLinks(): array
    {
        $links = [
            'terms' => home_url('/terms-of-service'),
            'privacy' => home_url('/privacy-policy'),
        ];

        return apply_filters('condrug_policy_links', $links);
    }

    protected function getPlanOptions(): array
    {
        $plans = [
            [
                'id' => 'starter',
                'name' => __('Starter', 'condrug'),
                'price' => __('$29 / month', 'condrug'),
                'description' => __('Perfect for individual practitioners starting digital consultations.', 'condrug'),
                'features' => [
                    __('Up to 3 active care flows', 'condrug'),
                    __('Email support', 'condrug'),
                    __('Stripe checkout coming soon', 'condrug'),
                ],
            ],
            [
                'id' => 'growth',
                'name' => __('Growth', 'condrug'),
                'price' => __('$59 / month', 'condrug'),
                'description' => __('Scale your practice with automation and priority reviews.', 'condrug'),
                'features' => [
                    __('Unlimited care flows', 'condrug'),
                    __('Priority support', 'condrug'),
                    __('Advanced analytics (beta)', 'condrug'),
                ],
            ],
            [
                'id' => 'enterprise',
                'name' => __('Enterprise', 'condrug'),
                'price' => __('Custom pricing', 'condrug'),
                'description' => __('Tailored solutions for clinics and multi-practice organizations.', 'condrug'),
                'features' => [
                    __('Dedicated success manager', 'condrug'),
                    __('Custom integrations', 'condrug'),
                    __('SLA-backed availability', 'condrug'),
                ],
            ],
        ];

        return apply_filters('condrug_plan_options', $plans);
    }

    protected function getUserAccessStatus(int $userId): array
    {
        $planId = get_user_meta($userId, 'condrug_plan_id', true);
        $planId = is_string($planId) ? trim($planId) : '';

        return [
            'has_plan' => $planId !== '',
            'plan_id' => $planId ?: null,
        ];
    }

    protected function getBaseUrl(): string
    {
        $post = get_post();
        if ($post instanceof \WP_Post) {
            return get_permalink($post);
        }

        return home_url('/');
    }

    public function getActionUrls(): array
    {
        $base = $this->getBaseUrl();
        $base = remove_query_arg(['condrug_action', 'condrug_notice'], $base);

        $urls = [
            'login' => add_query_arg('condrug_action', 'login', $base),
            'register' => add_query_arg('condrug_action', 'register', $base),
            'plan' => add_query_arg('condrug_action', 'plan', $base),
            'workspace' => add_query_arg('condrug_action', 'workspace', $base),
            'base' => $base,
        ];

        return apply_filters('condrug_action_urls', $urls, $base);
    }
}
