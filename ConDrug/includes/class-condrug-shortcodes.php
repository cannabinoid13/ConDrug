<?php

namespace ConDrug;

class Shortcodes
{
    protected Assets $assets;

    protected AccessManager $access;

    public function __construct(Assets $assets)
    {
        $this->assets = $assets;
        $this->access = new AccessManager();
    }

    public function register(): void
    {
        add_shortcode('condrug_workspace', [$this, 'renderWorkspace']);
        add_shortcode('condrug_openfda', [$this, 'renderOpenFDA']);
    }

    public function renderWorkspace($atts = [], $content = '', $tag = ''): string
    {
        $this->assets->enqueueFrontend();
        $context = $this->access->resolve();

        ob_start();
        $this->renderView($context);
        return (string) ob_get_clean();
    }

    protected function renderView(array $context): void
    {
        $template = $context['template'];
        $data = $context['data'];
        $meta = $context['meta'];

        if (is_readable($template)) {
            include $template;
            return;
        }

        echo esc_html__('Unable to load the requested view.', 'condrug');
    }

    public function renderOpenFDA($atts = [], $content = '', $tag = ''): string
    {
        $this->assets->enqueueOpenFDA();

        $template = CONDRUG_PLUGIN_DIR . 'templates/openfda.php';

        ob_start();
        if (is_readable($template)) {
            include $template;
        } else {
            echo '<div class="condrug-openfda"><p>' . esc_html__('OpenFDA görünümü yüklenemedi.', 'condrug') . '</p></div>';
        }
        return (string) ob_get_clean();
    }
}
