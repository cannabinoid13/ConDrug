<?php
/**
 * Admin settings template
 *
 * @var array $options
 */

$siteUrl = home_url('/');
$webhookBase = add_query_arg('condrug_stripeWebhook', '1', $siteUrl);
$newMemberCount = ConDrug\SettingsRepository::getInstance()->getNewMemberCount();
?>
<div class="wrap condrug-settings">
    <h1><?php esc_html_e('ConDrug Payment Settings', 'condrug'); ?></h1>

    <?php settings_errors('condrug_settings'); ?>

    <form method="post">
        <?php wp_nonce_field('condrug_settings_save', 'condrug_settings_nonce'); ?>

        <h2><?php esc_html_e('Stripe Credentials', 'condrug'); ?></h2>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="stripe_publishable_key"><?php esc_html_e('Publishable Key', 'condrug'); ?></label>
                    </th>
                    <td>
                        <input name="stripe_publishable_key" type="text" id="stripe_publishable_key" value="<?php echo esc_attr($options['stripe_publishable_key']); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e('Your Stripe publishable API key.', 'condrug'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="stripe_secret_key"><?php esc_html_e('Secret Key', 'condrug'); ?></label>
                    </th>
                    <td>
                        <input name="stripe_secret_key" type="password" id="stripe_secret_key" value="<?php echo esc_attr($options['stripe_secret_key']); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e('Your Stripe secret API key.', 'condrug'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="stripe_webhook_secret"><?php esc_html_e('Webhook Signing Secret', 'condrug'); ?></label>
                    </th>
                    <td>
                        <input name="stripe_webhook_secret" type="password" id="stripe_webhook_secret" value="<?php echo esc_attr($options['stripe_webhook_secret']); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e('Secret from your Stripe webhook endpoint for verifying events.', 'condrug'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2><?php esc_html_e('Webhook Configuration', 'condrug'); ?></h2>
        <p><?php esc_html_e('Use the following URLs when setting up Stripe event destinations for snapshot and thin payloads.', 'condrug'); ?></p>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><?php esc_html_e('Snapshot Payload Endpoint', 'condrug'); ?></th>
                    <td>
                        <code><?php echo esc_html($webhookBase); ?></code>
                        <p class="description"><?php esc_html_e('Add this URL as the destination when Stripe requests a snapshot payload endpoint.', 'condrug'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Thin Payload Endpoint', 'condrug'); ?></th>
                    <td>
                        <code><?php echo esc_html($webhookBase); ?></code>
                        <p class="description"><?php esc_html_e('Use the same URL for thin payload destinations — ConDrug automatically handles the payload style.', 'condrug'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="description">
            <?php
            printf(
                esc_html__('When Stripe asks for your endpoint URL, enter %1$s (this uses your site domain %2$s) and copy the generated signing secret back into the “Webhook Signing Secret” field above.', 'condrug'),
                '<code>' . esc_html($webhookBase) . '</code>',
                esc_html(parse_url($siteUrl, PHP_URL_HOST))
            );
            ?>
        </p>

        <h2><?php esc_html_e('Coupon Settings', 'condrug'); ?></h2>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="coupon_code"><?php esc_html_e('Coupon Code', 'condrug'); ?></label>
                    </th>
                    <td>
                        <input name="coupon_code" type="text" id="coupon_code" value="<?php echo esc_attr($options['coupon']['code']); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e('Optional coupon code users can enter during checkout.', 'condrug'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="coupon_percent_off"><?php esc_html_e('Percent Off', 'condrug'); ?></label>
                    </th>
                    <td>
                        <input name="coupon_percent_off" type="number" min="0" max="100" id="coupon_percent_off" value="<?php echo esc_attr($options['coupon']['percent_off']); ?>" class="small-text" />
                        <p class="description"><?php esc_html_e('Percentage discount applied when the coupon is used.', 'condrug'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2><?php esc_html_e('Plans & Pricing', 'condrug'); ?></h2>
        <?php foreach (['starter' => __('Starter', 'condrug'), 'growth' => __('Growth', 'condrug')] as $planId => $label) : ?>
            <h3><?php echo esc_html($label); ?></h3>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="plan_<?php echo esc_attr($planId); ?>_price_id"><?php esc_html_e('Stripe Price ID', 'condrug'); ?></label>
                        </th>
                        <td>
                            <input name="plan_<?php echo esc_attr($planId); ?>_price_id" type="text" id="plan_<?php echo esc_attr($planId); ?>_price_id" value="<?php echo esc_attr($options['plans'][$planId]['price_id']); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('Stripe recurring price ID for this plan (e.g., price_xxx).', 'condrug'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="plan_<?php echo esc_attr($planId); ?>_display_price"><?php esc_html_e('Display Price (USD)', 'condrug'); ?></label>
                        </th>
                        <td>
                            <input name="plan_<?php echo esc_attr($planId); ?>_display_price" type="number" step="0.01" id="plan_<?php echo esc_attr($planId); ?>_display_price" value="<?php echo esc_attr($options['plans'][$planId]['display_price']); ?>" class="regular-text" />
                            <label>
                                <input type="checkbox" name="plan_<?php echo esc_attr($planId); ?>_sync_price" value="1" <?php checked(!empty($options['plans'][$planId]['sync_price_with_stripe'])); ?> />
                                <?php esc_html_e('Sync price from Stripe automatically', 'condrug'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('If enabled, the plan price will be updated from the Stripe price ID above whenever checkout runs.', 'condrug'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Displayed Features', 'condrug'); ?></th>
                        <td>
                            <div class="condrug-features-list" data-plan="<?php echo esc_attr($planId); ?>">
                                <?php
                                $features = $options['plans'][$planId]['features'] ?? [];
                                if (empty($features)) {
                                    $features = [''];
                                }
                                foreach ($features as $featureIndex => $featureText) :
                                ?>
                                    <div class="condrug-feature-row">
                                        <input type="text" name="plan_<?php echo esc_attr($planId); ?>_features[]" value="<?php echo esc_attr($featureText); ?>" class="regular-text" />
                                        <button type="button" class="button condrug-feature-remove"><?php esc_html_e('Remove', 'condrug'); ?></button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="button condrug-feature-add" data-plan="<?php echo esc_attr($planId); ?>"><?php esc_html_e('Add Feature', 'condrug'); ?></button>
                            <p class="description"><?php esc_html_e('These items appear in the plan feature list on the public pricing screen.', 'condrug'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Usage Limits', 'condrug'); ?></th>
                        <td>
                            <?php foreach ($options['plans'][$planId]['limits'] as $limitKey => $limitValue) : ?>
                                <label for="plan_<?php echo esc_attr($planId); ?>_limits_<?php echo esc_attr($limitKey); ?>">
                                    <?php echo esc_html(ucwords(str_replace('_', ' ', $limitKey))); ?>
                                </label>
                                <input name="plan_<?php echo esc_attr($planId); ?>_limits[<?php echo esc_attr($limitKey); ?>]" type="number" id="plan_<?php echo esc_attr($planId); ?>_limits_<?php echo esc_attr($limitKey); ?>" value="<?php echo esc_attr($limitValue); ?>" class="small-text" />
                                <br />
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e('Configure feature limits enforced for this plan.', 'condrug'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        <?php endforeach; ?>

        <?php submit_button(__('Save Settings', 'condrug'), 'primary', 'condrug_settings_submit'); ?>
    </form>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:20px;">
        <?php wp_nonce_field('condrug_reset_member_count'); ?>
        <input type="hidden" name="action" value="condrug_reset_member_count" />
        <button type="submit" class="button">
            <?php esc_html_e('Reset new member counter', 'condrug'); ?>
        </button>
        <span class="description">
            <?php
            printf(
                esc_html__('Current pending count: %d', 'condrug'),
                (int) $newMemberCount
            );
            ?>
        </span>
    </form>
</div>
