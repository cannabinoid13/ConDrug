<?php

namespace ConDrug;

class AdminMenu
{
    protected Assets $assets;

    protected SettingsPage $settings;

    protected SubscribersPage $subscribers;

    protected SettingsRepository $repository;

    public function __construct(Assets $assets)
    {
        $this->assets = $assets;
        $this->settings = new SettingsPage();
        $this->subscribers = new SubscribersPage();
        $this->repository = SettingsRepository::getInstance();
    }

    public function register(): void
    {
        $newMemberCount = $this->repository->getNewMemberCount();
        $menuTitle = __('ConDrug', 'condrug');
        if ($newMemberCount > 0) {
            $menuTitle .= sprintf(' <span class="awaiting-mod">%d</span>', $newMemberCount);
        }

        add_menu_page(
            __('ConDrug Workspace', 'condrug'),
            $menuTitle,
            'manage_options',
            'condrug',
            [$this, 'renderPage'],
            $this->getIconUrl(),
            56
        );

        add_submenu_page(
            'condrug',
            __('Workspace', 'condrug'),
            __('Workspace', 'condrug'),
            'manage_options',
            'condrug',
            [$this, 'renderPage']
        );

        add_submenu_page(
            'condrug',
            __('Payment Settings', 'condrug'),
            __('Payment Settings', 'condrug'),
            'manage_options',
            'condrug-payment-settings',
            [$this->settings, 'render']
        );

        add_submenu_page(
            'condrug',
            __('Subscribers', 'condrug'),
            __('Subscribers', 'condrug'),
            'manage_options',
            'condrug-subscribers',
            [$this->subscribers, 'render']
        );
    }

    public function renderPage(): void
    {
        $this->assets->enqueueAdmin();
        include CONDRUG_PLUGIN_DIR . 'templates/workspace.php';
    }

    protected function getIconUrl(): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode($this->getSvgIcon());
    }

    protected function getSvgIcon(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3c5 0 8 3 8 9s-3 9-8 9" /><path d="M16 3c-5 0-8 3-8 9s3 9 8 9" /><path d="M6 8h12" /><path d="M6 16h12" /></svg>';
    }
}
