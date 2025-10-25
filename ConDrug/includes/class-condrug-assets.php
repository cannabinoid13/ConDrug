<?php

namespace ConDrug;

class Assets
{
    protected array $handles = [
        'admin_style' => 'condrug-admin-style',
        'frontend_style' => 'condrug-frontend-style',
        'admin_script' => 'condrug-admin-script',
        'frontend_script' => 'condrug-frontend-script',
        'stripe_js' => 'condrug-stripe-js',
    ];

    protected bool $registered = false;

    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $version = '0.1.0';

        wp_register_style(
            $this->handles['admin_style'],
            CONDRUG_PLUGIN_URL . 'assets/css/admin.css',
            [],
            $version
        );

        wp_register_style(
            $this->handles['frontend_style'],
            CONDRUG_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            $version
        );

        wp_register_script(
            $this->handles['admin_script'],
            CONDRUG_PLUGIN_URL . 'assets/js/admin.js',
            [],
            $version,
            true
        );

        wp_register_script(
            $this->handles['stripe_js'],
            'https://js.stripe.com/v3/',
            [],
            null
        );

        wp_register_script(
            $this->handles['frontend_script'],
            CONDRUG_PLUGIN_URL . 'assets/js/frontend.js',
            [$this->handles['stripe_js']],
            $version,
            true
        );

        $this->registered = true;
    }

    public function enqueueAdmin(): void
    {
        $this->register();
        wp_enqueue_style($this->handles['admin_style']);
        wp_enqueue_script($this->handles['admin_script']);
        wp_localize_script($this->handles['admin_script'], 'adminLocalization', [
            'removeText' => __('Remove', 'condrug'),
        ]);
    }

    public function enqueueFrontend(): void
    {
        $this->register();
        wp_enqueue_style($this->handles['frontend_style']);
        wp_enqueue_script($this->handles['frontend_script']);

        $settings = SettingsRepository::getInstance()->getOptions();
        wp_localize_script($this->handles['frontend_script'], 'condrugCheckout', [
            'nonce' => wp_create_nonce('condrug_checkout'),
            'coupon' => $settings['coupon']['code'] ?? '',
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'messages' => [
                'genericError' => __('Unable to start checkout. Please try again or contact support.', 'condrug'),
            ],
        ]);
    }

    public function getHandle(string $key): ?string
    {
        return $this->handles[$key] ?? null;
    }
}
