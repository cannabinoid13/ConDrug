<?php

namespace ConDrug;

class WebhookHandler
{
    protected StripeService $stripe;

    protected PlanManager $plans;

    public function __construct(StripeService $stripe)
    {
        $this->stripe = $stripe;
        $this->plans = new PlanManager();
    }

    public function hooks(): void
    {
        add_action('init', [$this, 'maybeHandleWebhook']);
    }

    public function maybeHandleWebhook(): void
    {
        if (!isset($_GET['condrug_stripeWebhook'])) {
            return;
        }

        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        try {
            $event = $this->stripe->verifyWebhook($payload, $signature);
        } catch (\Throwable $e) {
            status_header(400);
            exit;
        }

        if (!$event) {
            status_header(400);
            exit;
        }

        $this->dispatch($event);
        status_header(200);
        exit;
    }

    protected function dispatch(\Stripe\Event $event): void
    {
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutCompleted($event->data->object);
                break;
            case 'customer.subscription.deleted':
            case 'customer.subscription.updated':
                $this->handleSubscriptionEvent($event->data->object);
                break;
        }
    }

    protected function handleCheckoutCompleted($session): void
    {
        $userId = (int) ($session->metadata->user_id ?? 0);
        $planId = sanitize_key($session->metadata->plan_id ?? '');

        if ($userId && $planId) {
            $this->plans->assignPlan($userId, $planId, $session->subscription ?? null);
        }
    }

    protected function handleSubscriptionEvent($subscription): void
    {
        $userId = isset($subscription->metadata->user_id) ? (int) $subscription->metadata->user_id : 0;
        $planId = sanitize_key($subscription->metadata->plan_id ?? '');

        if (!$userId) {
            return;
        }

        if ('canceled' === $subscription->status) {
            $this->plans->removePlan($userId);
            return;
        }

        if ($planId) {
            $this->plans->assignPlan($userId, $planId, $subscription->id ?? null);
        }
    }
}
