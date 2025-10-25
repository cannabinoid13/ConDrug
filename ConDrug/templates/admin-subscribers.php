<?php
/**
 * Subscribers admin template
 *
 * @var array $views
 * @var string $active
 */
?>
<div class="wrap condrug-subscribers">
    <h1><?php esc_html_e('ConDrug Subscribers', 'condrug'); ?></h1>

    <h2 class="nav-tab-wrapper">
        <?php foreach ($views as $slug => $view) : ?>
            <a href="<?php echo esc_url(add_query_arg('condrug_view', $slug)); ?>" class="nav-tab <?php echo ($slug === $active) ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($view['label']); ?>
            </a>
        <?php endforeach; ?>
    </h2>

    <?php $activeView = $views[$active]; ?>

    <?php if (empty($activeView['members'])) : ?>
        <p><?php esc_html_e('No members found for this category yet.', 'condrug'); ?></p>
    <?php else : ?>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('User', 'condrug'); ?></th>
                    <th><?php esc_html_e('Email', 'condrug'); ?></th>
                    <th><?php esc_html_e('Subscription ID', 'condrug'); ?></th>
                    <th><?php esc_html_e('Joined', 'condrug'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activeView['members'] as $member) :
                    if (!$member['user'] instanceof WP_User) {
                        continue;
                    }
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url(get_edit_user_link($member['user']->ID)); ?>">
                                <?php echo esc_html($member['user']->display_name); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($member['user']->user_email); ?></td>
                        <td><?php echo esc_html($member['subscription_id'] ?: __('Unpaid', 'condrug')); ?></td>
                        <td><?php echo esc_html($member['joined'] ?: __('Unknown', 'condrug')); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
