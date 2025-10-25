<?php
/**
 * Workspace template
 *
 * @var array $data
 * @var array $meta
 */
?>
<div class="condrug-workspace">
    <header class="condrug-workspace__header">
        <h1>ConDrug Workspace</h1>
        <p>Build and manage your upcoming drug consultation workflows.</p>
    </header>

    <?php if (empty($meta['access']['has_plan'])) : ?>
        <aside class="condrug-workspace__notice">
            <p>
                <?php esc_html_e('You need to select a plan to unlock the full workspace experience.', 'condrug'); ?>
                <a class="condrug-link" href="<?php echo esc_url($meta['urls']['plan'] ?? '#'); ?>">
                    <?php esc_html_e('Choose a plan.', 'condrug'); ?>
                </a>
            </p>
        </aside>
    <?php endif; ?>

    <section class="condrug-workspace__body">
        <div class="condrug-workspace__placeholder">
            <p>This is a placeholder workspace. Future updates will integrate Stripe-powered experiences and additional tools.</p>
            <p>Use the shortcode <code>[condrug_workspace]</code> to render this workspace on any page.</p>
        </div>
    </section>

    <footer class="condrug-workspace__footer">
        <p>Need help? Visit our documentation (coming soon).</p>
    </footer>
</div>
