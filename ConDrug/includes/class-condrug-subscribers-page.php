<?php

namespace ConDrug;

class SubscribersPage
{
    protected MemberRepository $members;

    public function __construct()
    {
        $this->members = new MemberRepository();
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'condrug'));
        }

        $views = [
            'paid' => [
                'label' => __('Active Subscribers', 'condrug'),
                'members' => $this->hydrateMembers($this->members->getMembersByPaymentStatus(true)),
            ],
            'unpaid' => [
                'label' => __('Pending Payments', 'condrug'),
                'members' => $this->hydrateMembers($this->members->getMembersByPaymentStatus(false)),
            ],
        ];

        $active = sanitize_key($_GET['condrug_view'] ?? 'paid');
        if (!isset($views[$active])) {
            $active = 'paid';
        }

        include CONDRUG_PLUGIN_DIR . 'templates/admin-subscribers.php';
    }

    protected function hydrateMembers(array $rows): array
    {
        return array_map(function ($row) {
            $user = get_userdata((int) $row['user_id']);
            $subscriptionId = get_user_meta((int) $row['user_id'], 'condrug_subscription_id', true);
            return [
                'user' => $user,
                'subscription_id' => $subscriptionId,
                'metadata' => $row['metadata'],
                'joined' => $row['created_at'] ?? null,
            ];
        }, $rows);
    }
}
