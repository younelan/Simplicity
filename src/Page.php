<?php

namespace Opensitez\Simplicity;
use Opensitez\Simplicity\MSG;

class Page extends \Opensitez\Simplicity\Plugin
{
    private $sections = [];
    private $default_section;
    private $defaults = null;
    private $current_site = null;
    private $layout_name;
    private $style;
    private $layout;
    private $palette;
    private $blocks = [];

    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::PluginLoad:
                // Register this plugin as a route type handler for redirects
                $this->framework->register_type('routetype', 'page');
                break;
        }
        return parent::on_event($event);
    }
    
    function get_menus($app = [])
    {
        $menus = [
            "content" => [
                "text" => "Content",
                "image" => "genimgpage.png",
                "weight" => -2,
                "children" => [
                    "page" => ["plugin" => "page", "page" => "default", "text" => "Pages", "category" => "all"],
                ],
            ],
        ];
        return $menus;
    }
    public function set_layout($app = false)
    {
        $defaults = $this->config_object->get('defaults');
        $current_site = $this->config_object->get('site');
        $default_layout_name = $defaults['default-layout'] ?? "system";
        $this->layout_name = $this->app["layout"]
            ?? $current_site["layout"]
            ?? $defaults['layout']?? $defaults['default-layout']
            ?? "system";
        $system = $this->config_object->get('system');

        $current_layout = $current_site['definition']['layouts'][$this->layout_name] 
                          ?? $defaults['layouts'][$this->layout_name] ?? $defaults['layouts'][$default_layout_name]??[];
        
        $this->layout = $current_layout;

        if (isset($current_layout['blocks'])) {
            $this->blocks = $current_layout['blocks'];
        } else {
            $this->blocks = array_keys($current_site['definition']['blocks'] ?? []);
        }
        $this->default_section = $app['default-section'] ?? $this->layout['default-section'] ?? 
                                $current_site['definition']['default-section'] ?? $defaults['default-section'] ?? "content";
    }


    public function prepare($app = false)
    {
        $this->defaults = $this->config_object->get('defaults');
        $this->current_site = $this->config_object->get('site');

        $this->app = $app;
        //$config_object = $this->framework->getConfigObject();
        $this->set_layout();

        foreach ($this->layout['sections'] ?? [] as $idx => $value) {
            $this->add_section($idx,$value);
        };
        $this->add_blocks();
    }
    public function render($app = false)
    {
        $content = "";
        foreach ($this->sections as $idx => $current_section) {
            $content .= $current_section->on_render_page($app);
        }
        return $content;
    }
    public function on_render_page($app = false)
    {
        $content = "";
        $this->app = $app;
        $this->prepare($app);
        $content = $this->render($app);
        return $content;
    }
}
