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
}
