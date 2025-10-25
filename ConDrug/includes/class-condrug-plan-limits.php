<?php

namespace ConDrug;

class PlanLimits
{
    protected SettingsRepository $settings;

    public function __construct()
    {
        $this->settings = SettingsRepository::getInstance();
    }

    public function getLimits(string $planId): array
    {
        $plan = $this->settings->getOptions()['plans'][$planId] ?? null;
        return $plan['limits'] ?? [];
    }

    public function checkLimit(string $planId, string $limitKey, int $currentValue): bool
    {
        $limits = $this->getLimits($planId);
        if (!isset($limits[$limitKey])) {
            return true;
        }

        $max = (int) $limits[$limitKey];
        if (0 === $max) {
            return true;
        }

        return $currentValue < $max;
    }

    public function getLimitValue(string $planId, string $limitKey): ?int
    {
        $limits = $this->getLimits($planId);
        return isset($limits[$limitKey]) ? (int) $limits[$limitKey] : null;
    }
}
