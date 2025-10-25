<?php

namespace ConDrug;

use Stripe\Checkout\Session;
use Stripe\Coupon;
use Stripe\StripeClient;

class StripeService
{
    protected SettingsRepository $settings;

    protected ?StripeClient $client = null;

    public function __construct()
    {
        $this->settings = SettingsRepository::getInstance();
    }

    public function isConfigured(): bool
    {
        $options = $this->settings->getOptions();

        return !empty($options['stripe_publishable_key']) && !empty($options['stripe_secret_key']);
    }

    public function getClient()
    {
        if (!class_exists(StripeClient::class)) {
            return null;
        }

        if (!$this->isConfigured()) {
            return null;
        }

        if (null === $this->client) {
            $options = $this->settings->getOptions();
            $this->client = new StripeClient($options['stripe_secret_key']);
        }

        return $this->client;
    }

    public function createCheckoutSession(int $userId, string $planId, ?string $couponCode = null)
    {
        if (!$this->isConfigured()) {
            return new \WP_Error('condrug_stripe_not_configured', __('Stripe keys are missing. Please add them in Payment Settings.', 'condrug'));
        }

        $client = $this->getClient();

        if (!$client) {
            return new \WP_Error('condrug_stripe_sdk_missing', __('Stripe PHP SDK is not installed. Run composer require stripe/stripe-php.', 'condrug'));
        }

        $plan = $this->getPlanConfig($planId);

        if (!$plan || empty($plan['price_id'])) {
            return new \WP_Error('condrug_stripe_plan_invalid', __('Selected plan is not configured with a Stripe price ID.', 'condrug'));
        }

        $couponId = null;
        try {
            $couponId = $this->resolveCouponId($client, $couponCode);
        } catch (\Throwable $exception) {
            return new \WP_Error('condrug_stripe_coupon_error', $exception->getMessage());
        }

        $url = $this->getWorkspaceUrl();

        $params = [
            'mode' => 'subscription',
            'customer_email' => $this->resolveUserEmail($userId),
            'line_items' => [
                [
                    'price' => $plan['price_id'],
                    'quantity' => 1,
                ],
            ],
            'success_url' => add_query_arg('condrug_action', 'plan', $url),
            'cancel_url' => add_query_arg('condrug_action', 'plan', $url),
            'metadata' => [
                'user_id' => $userId,
                'plan_id' => $planId,
            ],
        ];

        if ($couponId) {
            $params['discounts'] = [
                [
                    'coupon' => $couponId,
                ],
            ];
        }

        try {
            return $client->checkout->sessions->create($params);
        } catch (\Throwable $exception) {
            return new \WP_Error('condrug_stripe_exception', $exception->getMessage());
        }
    }

    public function verifyWebhook(string $payload, string $signature): ?\Stripe\Event
    {
        $options = $this->settings->getOptions();
        $secret = $options['stripe_webhook_secret'] ?? '';

        if (!$secret) {
            return null;
        }

        return \Stripe\Webhook::constructEvent($payload, $signature, $secret);
    }

    public function getPublishableKey(): string
    {
        $options = $this->settings->getOptions();
        return $options['stripe_publishable_key'] ?? '';
    }

    public function getPlanConfig(string $planId): ?array
    {
        $options = $this->settings->getOptions();
        $plan = $options['plans'][$planId] ?? null;

        if ($plan && !empty($plan['sync_price_with_stripe']) && !empty($plan['price_id'])) {
            $client = $this->getClient();
            if ($client) {
                try {
                    $price = $client->prices->retrieve($plan['price_id'], []);
                    if (!empty($price->unit_amount)) {
                        $plan['display_price'] = $price->unit_amount / 100;
                    }
                } catch (\Throwable $exception) {
                    // Leave display price as stored if retrieval fails.
                }
            }
        }

        return $plan;
    }

    public function getDisplayPrice(string $planId): float
    {
        $plan = $this->getPlanConfig($planId);
        return isset($plan['display_price']) ? (float) $plan['display_price'] : 0.0;
    }

    public function getFeatures(string $planId): array
    {
        $plan = $this->getPlanConfig($planId);
        return $plan['features'] ?? [];
    }

    protected function resolveUserEmail(int $userId): string
    {
        $user = get_userdata($userId);
        return $user ? (string) $user->user_email : '';
    }

    protected function resolveCouponId(StripeClient $client, ?string $code)
    {
        $options = $this->settings->getOptions();
        $settingsCode = $options['coupon']['code'] ?? '';
        $percent = (int) ($options['coupon']['percent_off'] ?? 0);

        if (!$code || !$settingsCode || !hash_equals(strtolower($settingsCode), strtolower($code)) || $percent <= 0) {
            return null;
        }

        $couponId = trim($settingsCode);

        try {
            $coupon = $client->coupons->retrieve($couponId, []);
            if ($coupon instanceof Coupon) {
                return $coupon->id;
            }
        } catch (\Stripe\Exception\InvalidRequestException $exception) {
            // Coupon not found; fall through to create it.
        }

        $coupon = $client->coupons->create([
            'id' => $couponId,
            'duration' => 'once',
            'percent_off' => $percent,
            'name' => sprintf('%s (%d%% off)', $couponId, $percent),
        ]);

        if ($coupon instanceof Coupon) {
            return $coupon->id;
        }

        return null;
    }

    protected function getWorkspaceUrl(): string
    {
        $post = get_post();
        if ($post instanceof \WP_Post) {
            return get_permalink($post);
        }

        return home_url('/');
    }
}
