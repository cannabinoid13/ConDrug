<?php

namespace ConDrug;

class SettingsRepository
{
    protected const OPTION_KEY = 'condrug_settings';

    protected static ?SettingsRepository $instance = null;

    protected array $defaults = [
        'stripe_publishable_key' => '',
        'stripe_secret_key' => '',
        'stripe_webhook_secret' => '',
        'coupon' => [
            'code' => '',
            'percent_off' => 0,
        ],
        'plans' => [
            'starter' => [
                'price' => 99,
                'price_id' => '',
                'sync_price_with_stripe' => false,
                'display_price' => 29,
                'features' => [
                    'Up to 3 active care flows',
                    'Email support',
                    'Stripe checkout coming soon',
                ],
                'limits' => [
                    'active_flows' => 3,
                    'team_members' => 1,
                ],
            ],
            'growth' => [
                'price' => 199,
                'price_id' => '',
                'sync_price_with_stripe' => false,
                'display_price' => 59,
                'features' => [
                    'Unlimited care flows',
                    'Priority support',
                    'Advanced analytics (beta)',
                ],
                'limits' => [
                    'active_flows' => 999,
                    'team_members' => 5,
                ],
            ],
        ],
        'new_member_count' => 0,
    ];

    public static function getInstance(): SettingsRepository
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function getOptions(): array
    {
        $stored = get_option(static::OPTION_KEY, []);
        return wp_parse_args($stored, $this->defaults);
    }

    public function updateOptions(array $options): void
    {
        update_option(static::OPTION_KEY, $options, true);
    }

    public function handlePost(): void
    {
        if (!isset($_POST['condrug_settings_submit'])) {
            return;
        }

        check_admin_referer('condrug_settings_save', 'condrug_settings_nonce');

        $current = $this->getOptions();

        $current['stripe_publishable_key'] = sanitize_text_field($_POST['stripe_publishable_key'] ?? '');
        $current['stripe_secret_key'] = sanitize_text_field($_POST['stripe_secret_key'] ?? '');
        $current['stripe_webhook_secret'] = sanitize_text_field($_POST['stripe_webhook_secret'] ?? '');

        $current['coupon']['code'] = sanitize_text_field($_POST['coupon_code'] ?? '');
        $current['coupon']['percent_off'] = (int) ($_POST['coupon_percent_off'] ?? 0);

        foreach (['starter', 'growth'] as $planId) {
            $priceKey = sprintf('plan_%s_price_id', $planId);
            $limitsKey = sprintf('plan_%s_limits', $planId);
            $displayPriceKey = sprintf('plan_%s_display_price', $planId);
            $featuresKey = sprintf('plan_%s_features', $planId);
            $syncKey = sprintf('plan_%s_sync_price', $planId);

            $current['plans'][$planId]['price_id'] = sanitize_text_field($_POST[$priceKey] ?? '');
            $current['plans'][$planId]['display_price'] = (float) ($_POST[$displayPriceKey] ?? $current['plans'][$planId]['display_price']);
            $current['plans'][$planId]['sync_price_with_stripe'] = !empty($_POST[$syncKey]);

            if (!empty($_POST[$featuresKey]) && is_array($_POST[$featuresKey])) {
                $features = array_map('sanitize_text_field', array_filter(array_map('trim', $_POST[$featuresKey])));
                $current['plans'][$planId]['features'] = $features ?: $this->defaults['plans'][$planId]['features'];
            }

            if (!empty($_POST[$limitsKey]) && is_array($_POST[$limitsKey])) {
                foreach ($_POST[$limitsKey] as $limitKey => $value) {
                    $current['plans'][$planId]['limits'][$limitKey] = (int) $value;
                }
            }
        }

        $this->updateOptions($current);

        add_settings_error('condrug_settings', 'settings_updated', __('Settings saved.', 'condrug'), 'updated');
    }

    public function getNewMemberCount(): int
    {
        $options = $this->getOptions();
        return (int) ($options['new_member_count'] ?? 0);
    }

    public function incrementNewMemberCount(): void
    {
        $this->adjustNewMemberCount(1);
    }

    public function resetNewMemberCount(): void
    {
        $options = $this->getOptions();
        $options['new_member_count'] = 0;
        $this->updateOptions($options);
    }

    public function adjustNewMemberCount(int $delta): void
    {
        $options = $this->getOptions();
        $current = (int) ($options['new_member_count'] ?? 0);
        $options['new_member_count'] = max(0, $current + $delta);
        $this->updateOptions($options);
    }
}
