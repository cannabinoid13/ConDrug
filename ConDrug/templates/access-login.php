<?php
/**
 * Login view template
 *
 * @var array $data
 * @var array $meta
 */
?>
<div class="condrug-access condru-variant-login">
    <header class="condrug-access__header">
        <h1><?php esc_html_e('Sign in to ConDrug', 'condrug'); ?></h1>
        <p><?php esc_html_e('Access your consultation workspace and manage your plans.', 'condrug'); ?></p>
    </header>

    <?php if (!empty($meta['notice'])) : ?>
        <div class="condrug-notice condrug-notice--<?php echo esc_attr($meta['notice']['type']); ?>">
            <?php echo esc_html($meta['notice']['text'] ?? ''); ?>
        </div>
    <?php endif; ?>

    <form class="condrug-access__form" method="post" action="<?php echo esc_url($meta['urls']['base']); ?>">
        <?php wp_nonce_field('condrug_login', '_condrug_login_nonce'); ?>
        <input type="hidden" name="condrug_action" value="login" />
        <input type="hidden" name="redirect_to" value="<?php echo esc_attr($meta['urls']['plan'] ?? ''); ?>" />
        <div class="condrug-field">
            <label for="condrug-login-email"><?php esc_html_e('Email Address', 'condrug'); ?></label>
            <input type="email" id="condrug-login-email" name="log" required autocomplete="email" value="<?php echo esc_attr($data['prefill']['log'] ?? ''); ?>" />
        </div>
        <div class="condrug-field">
            <label for="condrug-login-password"><?php esc_html_e('Password', 'condrug'); ?></label>
            <input type="password" id="condrug-login-password" name="pwd" required autocomplete="current-password" />
        </div>
        <div class="condrug-field condrog-field--actions">
            <label class="condrug-checkbox">
                <input type="checkbox" name="rememberme" value="forever" />
                <span><?php esc_html_e('Remember me on this device', 'condrug'); ?></span>
            </label>
            <a class="condrug-link" href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('Forgot password?', 'condrug'); ?></a>
        </div>
        <button type="submit" class="condrug-button">
            <?php esc_html_e('Sign In', 'condrug'); ?>
        </button>
    </form>

    <footer class="condrug-access__footer">
        <p>
            <?php esc_html_e('New to ConDrug?', 'condrug'); ?>
            <a class="condrug-link" href="<?php echo esc_url($meta['urls']['register'] ?? '#'); ?>">
                <?php esc_html_e('Create an account', 'condrug'); ?>
            </a>
        </p>
    </footer>
</div>
