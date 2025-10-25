<?php

namespace ConDrug;

class PlanManager
{
    protected PlanLimits $limits;

    protected SettingsRepository $settings;

    public function __construct()
    {
        $this->limits = new PlanLimits();
        $this->settings = SettingsRepository::getInstance();
    }

    public function assignPlan(int $userId, string $planId, ?string $subscriptionId = null): void
    {
        update_user_meta($userId, 'condrug_plan_id', $planId);
        if ($subscriptionId) {
            update_user_meta($userId, 'condrug_subscription_id', $subscriptionId);
        }
    }

    public function removePlan(int $userId): void
    {
        delete_user_meta($userId, 'condrug_plan_id');
        delete_user_meta($userId, 'condrug_subscription_id');
    }

    public function getUserPlan(int $userId): ?string
    {
        $planId = get_user_meta($userId, 'condrug_plan_id', true);
        return $planId ? (string) $planId : null;
    }

    public function canUserPerform(int $userId, string $limitKey, int $currentValue): bool
    {
        $planId = $this->getUserPlan($userId);
        if (!$planId) {
            return false;
        }

        return $this->limits->checkLimit($planId, $limitKey, $currentValue);
    }

    public function getLimitValue(int $userId, string $limitKey): ?int
    {
        $planId = $this->getUserPlan($userId);
        if (!$planId) {
            return null;
        }

        return $this->limits->getLimitValue($planId, $limitKey);
    }
}
