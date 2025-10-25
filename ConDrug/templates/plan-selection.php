<?php
/**
 * Plan selection view template
 *
 * @var array $data
 * @var array $meta
 */

$options = ConDrug\SettingsRepository::getInstance()->getOptions();
$stripeService = ConDrug\Plugin::boot()->stripe();
$couponCode = $options['coupon']['code'] ?? '';
$couponPercent = $options['coupon']['percent_off'] ?? 0;
?>
<div class="condrug-plan-selection" data-publishable-key="<?php echo esc_attr($options['stripe_publishable_key'] ?? ''); ?>" data-coupon="<?php echo esc_attr($couponCode); ?>">
    <header class="condrug-plan-selection__header">
        <h1><?php esc_html_e('Select your plan', 'condrug'); ?></h1>
        <p><?php esc_html_e('Choose the right plan to unlock the ConDrug workspace.', 'condrug'); ?></p>
        <?php if (!empty($couponCode) && (int) $couponPercent > 0) : ?>
            <p class="condrug-plan-selection__coupon">
                <?php
                printf(
                    esc_html__('Use coupon code %1$s to receive %2$d%% off at checkout.', 'condrug'),
                    '<code>' . esc_html($couponCode) . '</code>',
                    (int) $couponPercent
                );
                ?>
            </p>
        <?php endif; ?>
    </header>

    <?php if (!empty($meta['notice'])) : ?>
        <div class="condrug-notice condrug-notice--<?php echo esc_attr($meta['notice']['type']); ?>">
            <?php echo esc_html($meta['notice']['text'] ?? ''); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($data['status']['plan_id'])) : ?>
        <div class="condrug-plan-selection__status">
            <p>
                <?php
                printf(
                    esc_html__('You are currently subscribed to the %s plan.', 'condrug'),
                    esc_html(ucfirst($data['status']['plan_id']))
                );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <section class="condrug-plan-selection__plans">
        <?php foreach ($data['plans'] as $plan) :
            $displayPrice = $stripeService->getDisplayPrice($plan['id']);
            $features = $stripeService->getFeatures($plan['id']);
        ?>
            <article class="condrug-plan-card" data-plan-id="<?php echo esc_attr($plan['id']); ?>">
                <header>
                    <h2><?php echo esc_html($plan['name']); ?></h2>
                    <p class="condrug-plan-card__price"><?php echo esc_html(sprintf('$%s / month', number_format_i18n($displayPrice, 2))); ?></p>
                    <p class="condrug-plan-card__description"><?php echo esc_html($plan['description']); ?></p>
                </header>

                <ul>
                    <?php foreach ($features as $feature) : ?>
                        <li><?php echo esc_html($feature); ?></li>
                    <?php endforeach; ?>
                </ul>

                <button
                    type="button"
                    class="condrug-button condrug-button--primary"
                    data-condrug-checkout
                    data-plan-id="<?php echo esc_attr($plan['id']); ?>"
                >
                    <?php esc_html_e('Pay & Activate', 'condrug'); ?>
                </button>
            </article>
        <?php endforeach; ?>
    </section>

    <footer class="condrug-plan-selection__footer">
        <p><?php esc_html_e('Complete your payment to activate workspace access instantly.', 'condrug'); ?></p>
    </footer>
</div>
