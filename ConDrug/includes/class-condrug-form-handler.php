<?php

namespace ConDrug;

class FormHandler
{
    protected AccessManager $access;

    public function __construct()
    {
        $this->access = new AccessManager();
    }

    public function hooks(): void
    {
        add_action('init', [$this, 'maybeHandlePlanSelection']);
        add_action('init', [$this, 'maybeHandleLogin']);
        add_action('init', [$this, 'maybeHandleRegistration']);
    }

    protected function cleanUrl(string $url): string
    {
        return remove_query_arg(['condrug_notice', 'condrug_action'], $url);
    }

    protected function log(string $event, array $context = []): void
    {
        do_action('condrug_form_event', $event, $context);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[ConDrug] ' . $event . ': ' . wp_json_encode($context));
        }
    }

    protected function redirectWithMessage(string $url, array $message): void
    {
        $url = $this->cleanUrl($url);
        $args = [
            'condrug_notice' => rawurlencode(wp_json_encode($message)),
        ];

        $this->log('redirect_with_message', [
            'url' => $url,
            'message' => $message,
        ]);

        wp_safe_redirect(add_query_arg($args, $url));
        exit;
    }

    protected function getBaseRedirect(?string $fallback = null): string
    {
        $urls = $this->access->getActionUrls();
        $base = $urls['base'] ?? home_url('/');
        return $fallback ?: $base;
    }

    protected function isAction(string $action): bool
    {
        return isset($_POST['condrug_action']) && $_POST['condrug_action'] === $action;
    }

    public function maybeHandleLogin(): void
    {
        if (!$this->isAction('login')) {
            return;
        }

        if (!isset($_POST['_condrug_login_nonce']) || !wp_verify_nonce($_POST['_condrug_login_nonce'], 'condrug_login')) {
            $this->log('login_nonce_failed');
            return;
        }

        $redirect = isset($_POST['redirect_to']) ? esc_url_raw(wp_unslash($_POST['redirect_to'])) : $this->access->getActionUrls()['plan'];

        $creds = [
            'user_login' => sanitize_user($_POST['log'] ?? ''),
            'user_password' => $_POST['pwd'] ?? '',
            'remember' => !empty($_POST['rememberme']),
        ];

        $user = wp_signon($creds, is_ssl());

        if (is_wp_error($user)) {
            $this->log('login_failed', ['error' => $user->get_error_message()]);
            $this->redirectWithMessage(
                $this->access->getActionUrls()['login'],
                [
                    'type' => 'error',
                    'text' => $user->get_error_message(),
                ]
            );
        }

        $this->log('login_success', ['user_id' => $user->ID ?? 0]);

        $redirect = $redirect ?: $this->access->getActionUrls()['plan'];
        $redirect = remove_query_arg('condrug_notice', $redirect);

        wp_safe_redirect($redirect);
        exit;
    }

    public function maybeHandleRegistration(): void
    {
        if (!$this->isAction('register')) {
            return;
        }

        if (!isset($_POST['_condrug_register_nonce']) || !wp_verify_nonce($_POST['_condrug_register_nonce'], 'condrug_register')) {
            $this->log('registration_nonce_failed');
            return;
        }

        if (is_user_logged_in()) {
            return;
        }

        $redirect = isset($_POST['redirect_to']) ? esc_url_raw(wp_unslash($_POST['redirect_to'])) : $this->access->getActionUrls()['plan'];

        $email = sanitize_email($_POST['user_email'] ?? '');
        $username = sanitize_user($email, true);
        $password = $_POST['user_pass'] ?? wp_generate_password();

        $errors = new \WP_Error();

        if (empty($email) || !is_email($email)) {
            $errors->add('invalid_email', __('Please provide a valid email address.', 'condrug'));
        }

        if (empty($password)) {
            $errors->add('invalid_password', __('Please choose a password.', 'condrug'));
        }

        if (email_exists($email)) {
            $errors->add('email_exists', __('This email is already registered. Please sign in.', 'condrug'));
        }

        if (empty($_POST['condrug_accept_terms'])) {
            $errors->add('terms_required', __('You must accept the Terms of Service.', 'condrug'));
        }

        if (empty($_POST['condrug_accept_privacy'])) {
            $errors->add('privacy_required', __('You must accept the Privacy Policy.', 'condrug'));
        }

        if ($errors->has_errors()) {
            $this->log('registration_failed_validation', ['errors' => $errors->get_error_messages()]);
            $this->redirectWithMessage(
                $this->access->getActionUrls()['register'],
                [
                    'type' => 'error',
                    'text' => implode(' ', $errors->get_error_messages()),
                ]
            );
        }

        $userId = wp_create_user($username, $password, $email);

        if (is_wp_error($userId)) {
            $this->log('registration_create_failed', ['error' => $userId->get_error_message()]);
            $this->redirectWithMessage(
                $this->access->getActionUrls()['register'],
                [
                    'type' => 'error',
                    'text' => $userId->get_error_message(),
                ]
            );
        }

        $this->log('registration_success', ['user_id' => $userId]);

        wp_update_user([
            'ID' => $userId,
            'first_name' => sanitize_text_field($_POST['first_name'] ?? ''),
            'last_name' => sanitize_text_field($_POST['last_name'] ?? ''),
        ]);

        wp_signon([
            'user_login' => $username,
            'user_password' => $password,
        ], is_ssl());

        $this->redirectWithMessage(
            $redirect ?: $this->access->getActionUrls()['plan'],
            [
                'type' => 'success',
                'text' => __('Account created. Let’s pick your plan next.', 'condrug'),
            ]
        );
    }

    public function maybeHandlePlanSelection(): void
    {
        if (!$this->isAction('plan')) {
            return;
        }

        if (!isset($_POST['_condrug_select_plan_nonce']) || !wp_verify_nonce($_POST['_condrug_select_plan_nonce'], 'condrug_select_plan')) {
            $this->log('plan_nonce_failed');
            return;
        }

        if (!is_user_logged_in()) {
            $this->log('plan_requires_login');
            $this->redirectWithMessage(
                $this->access->getActionUrls()['login'],
                [
                    'type' => 'error',
                    'text' => __('You need to sign in before selecting a plan.', 'condrug'),
                ]
            );
        }

        $planId = sanitize_key($_POST['condrug_plan_id'] ?? '');
        $available = apply_filters('condrug_plan_options', []);
        $valid = wp_list_pluck($available, 'id');

        if (!in_array($planId, $valid, true)) {
            $this->log('plan_invalid_choice', ['plan_id' => $planId]);
            $this->redirectWithMessage(
                $this->access->getActionUrls()['plan'],
                [
                    'type' => 'error',
                    'text' => __('Please choose a valid plan.', 'condrug'),
                ]
            );
        }

        update_user_meta(get_current_user_id(), 'condrug_plan_id', $planId);

        $this->log('plan_selected', ['plan_id' => $planId, 'user_id' => get_current_user_id()]);

        $this->redirectWithMessage(
            $this->access->getActionUrls()['workspace'],
            [
                'type' => 'success',
                'text' => __('Plan saved. Workspace access unlocked.', 'condrug'),
            ]
        );
    }
}
