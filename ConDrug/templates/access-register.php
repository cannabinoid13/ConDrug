<?php
/**
 * Registration view template
 *
 * @var array $data
 * @var array $meta
 */
?>
<div class="condrug-access condrog-variant-register">
    <header class="condrug-access__header">
        <h1><?php esc_html_e('Create your ConDrug account', 'condrug'); ?></h1>
        <p><?php esc_html_e('Register to access digital drug consultation workflows and future billing options.', 'condrug'); ?></p>
    </header>

    <?php if (!empty($meta['notice'])) : ?>
        <div class="condrug-notice condrug-notice--<?php echo esc_attr($meta['notice']['type']); ?>">
            <?php echo esc_html($meta['notice']['text'] ?? ''); ?>
        </div>
    <?php endif; ?>

    <form class="condrug-access__form" method="post" action="<?php echo esc_url($meta['urls']['base']); ?>">
        <?php wp_nonce_field('condrug_register', '_condrug_register_nonce'); ?>
        <input type="hidden" name="condrug_action" value="register" />
        <input type="hidden" name="redirect_to" value="<?php echo esc_attr($meta['urls']['plan'] ?? ''); ?>" />

        <div class="condrug-field">
            <label for="condrug-register-firstname"><?php esc_html_e('First Name', 'condrug'); ?></label>
            <input type="text" id="condrug-register-firstname" name="first_name" required autocomplete="given-name" value="<?php echo esc_attr($data['prefill']['first_name'] ?? ''); ?>" />
        </div>

        <div class="condrug-field">
            <label for="condrug-register-lastname"><?php esc_html_e('Last Name', 'condrug'); ?></label>
            <input type="text" id="condrug-register-lastname" name="last_name" required autocomplete="family-name" value="<?php echo esc_attr($data['prefill']['last_name'] ?? ''); ?>" />
        </div>

        <div class="condrug-field">
            <label for="condrug-register-email"><?php esc_html_e('Email Address', 'condrug'); ?></label>
            <input type="email" id="condrug-register-email" name="user_email" required autocomplete="email" value="<?php echo esc_attr($data['prefill']['user_email'] ?? ''); ?>" />
        </div>

        <div class="condrug-field">
            <label for="condrug-register-password"><?php esc_html_e('Password', 'condrug'); ?></label>
            <input type="password" id="condrug-register-password" name="user_pass" required autocomplete="new-password" />
        </div>

        <fieldset class="condrug-fieldset">
            <legend><?php esc_html_e('Agreements', 'condrug'); ?></legend>
            <label class="condrug-checkbox">
                <input type="checkbox" name="condrug_accept_terms" value="1" required />
                <span>
                    <?php
                    printf(
                        esc_html__('I accept the %1$s and %2$s.', 'condrug'),
                        sprintf('<a class="condrug-link" href="%s" target="_blank" rel="noopener">%s</a>', esc_url($data['policy_links']['terms']), esc_html__('Terms of Service', 'condrug')),
                        sprintf('<a class="condrug-link" href="%s" target="_blank" rel="noopener">%s</a>', esc_url($data['policy_links']['privacy']), esc_html__('Privacy Policy', 'condrug'))
                    );
                    ?>
                </span>
            </label>
            <label class="condrug-checkbox">
                <input type="checkbox" name="condrug_accept_privacy" value="1" required />
                <span><?php esc_html_e('I consent to the processing of my personal data for consultation purposes.', 'condrug'); ?></span>
            </label>
        </fieldset>

        <fieldset class="condrug-fieldset">
            <legend><?php esc_html_e('Security options (coming soon)', 'condrug'); ?></legend>
            <ul class="condrug-security-list">
                <li><?php esc_html_e('Email verification will be required before workspace access.', 'condrug'); ?></li>
                <li><?php esc_html_e('Multi-factor authentication (MFA) can be enabled for added protection.', 'condrug'); ?></li>
            </ul>
        </fieldset>

        <button type="submit" class="condrug-button">
            <?php esc_html_e('Register and Continue', 'condrug'); ?>
        </button>
    </form>

    <footer class="condrug-access__footer">
        <p>
            <?php esc_html_e('Already have an account?', 'condrug'); ?>
            <a class="condrug-link" href="<?php echo esc_url($meta['urls']['login'] ?? '#'); ?>">
                <?php esc_html_e('Sign in instead', 'condrug'); ?>
            </a>
        </p>
    </footer>
</div>
