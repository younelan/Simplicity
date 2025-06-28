<?php

namespace Opensitez\Simplicity\Plugins;
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

    /* legacy, should eventually disappear, plugins should add a section rather than render themselves */
    // function render_inserts($inserts, $app)
    // {
    //     $section_object = $this->plugins->get_plugin("section");
    //     return $section_object->render_section_contents($inserts, $app);
    // }

    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::PluginLoad:
                // Register this plugin as a route type handler for redirects
                $this->plugins->register_type('routetype', 'page');
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
        // $debug = $this->plugins->get_plugin('debug');
        // print "<h1>Defaults</h1>";
        // $defaults = $this->config_object->get('defaults');
        // echo $debug->printArray($defaults);
        
        // print "<h1>Current Site</h1>";
        // $current_site = $this->config_object->get('site');
        // echo $debug->printArray($current_site);
        $this->layout_name = $this->app["layout"]
            ?? $current_site["layout"]
            ?? $defaults['layout']?? $defaults['default-layout']
            ?? "system";
        if (isset($current_site['layouts'][$this->layout_name??""])) {
            $this->layout = $current_site['layouts'][$this->layout_name];
            if (isset($this->layout['blocks'])) {
                $this->blocks = $current_site['layouts'][$this->layout_name]['blocks'];
            } else {
                $this->blocks = array_keys($current_site['blocks']);
            }
        } elseif (isset($defaults['layouts'][$this->layout_name??false])) {
            $this->layout = $defaults['layouts'][$this->layout_name] ?? [];
            $this->blocks = $defaults['layouts'][$this->layout_name]['blocks'] ?? [];
        } else {
            $this->layout_name = $defaults['layout']?? $defaults['layout'] ?? "system";
            $this->layout = $defaults['layouts'][$this->layout_name]??[];
            $this->blocks = $defaults['layouts'][$this->layout_name]['blocks']??[];
        }
        $this->default_section = $this->layout['default-section'] ?? $defaults['default-section'] ?? "content";
    }
    public function create_sections()
    {
        foreach ($this->layout['sections'] ?? [] as $idx => $value) {
            $value['name'] = $idx;
            $new_section = new Section($this->config_object);
            $new_section->set_handler($this->plugins);
            $new_section->set_section_options($value);
            $this->sections[$idx] = $new_section;
        }
    }
    function add_blocks($blocks = false, $section = false)
    {
        $defaults = $this->config_object->get('defaults');
        $current_site = $this->config_object->get('site');
        if (!$section) {
            $default_section = $this->app['default-section']
                ?? $this->layout['default-section']
                ?? $this->current_site['vars']['default-section']
                ?? $defaults['vars']['default-section']?? "content";
        } else {
            $default_section = $section;
        }
        if (!$blocks) {
            $blocks = $this->blocks;
        }
        foreach ($blocks ?? [] as $idx => $block_name) {
            $current_block = $this->current_site['blocks'][$block_name] ?? [];
            $current_block['name'] = $block_name;
            if ($current_block) {
                $section_name = $current_block['section'] ?? $default_section;
                if (isset($this->sections[$section_name])) {
                    $this->sections[$section_name]->add_block($current_block, $block_name);
                }
            }
        }
    }
    function add_block($block, $section = false)
    {
        if ($section == false)
            $section = $this->default_section;
        if (isset($this->sections[$section]))
            $this->sections[$section]->add_block($block);
    }
    public function prepare($app = false)
    {
        $this->defaults = $this->config_object->get('defaults');
        $this->current_site = $this->config_object->get('site');

        $this->app = $app;
        //$config_object = $this->plugins->getConfigObject();
        $this->set_layout();

        $this->create_sections();
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
