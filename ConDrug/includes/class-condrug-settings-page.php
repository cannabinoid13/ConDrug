<?php

namespace ConDrug;

class SettingsPage
{
    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'condrug'));
        }

        SettingsRepository::getInstance()->handlePost();

        $options = SettingsRepository::getInstance()->getOptions();

        include CONDRUG_PLUGIN_DIR . 'templates/admin-settings.php';
    }
}
