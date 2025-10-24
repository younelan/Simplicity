<?php

namespace Opensitez\Simplicity;
use Opensitez\Simplicity\MSG;

class Page extends \Opensitez\Simplicity\Component
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
            case MSG::onComponentLoad:
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
                    "page" => ["component" => "page", "page" => "default", "text" => "Pages", "category" => "all"],
                ],
            ],
        ];
        return $menus;
    }
    public function set_layout($app = false)
    {
        $defaults = $this->config_object->get('defaults');
        $current_route = $this->config_object->get('site.current-route', []);
        $current_site = $this->config_object->get('site');

        $default_layout_name =  $defaults['default-layout'] ?? "system";
        $this->layout_name = $this->app["layout"]
            ?? $current_site['definition']['vars']["layout"]
            ?? $defaults['layout']?? $defaults['default-layout']
            ?? "system";
        //print "-- Layout Name: {$this->layout_name} --\n";
        //print_r($current_site);
        //exit;
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
        $this->add_blocks($this->current_site['definition']['blocks'] ?? []);

        $this->app = $app;
        //$config_object = $this->framework->getConfigObject();
        $this->set_layout();
        //print "-- Layout: {$this->layout_name} --\n<pre>";
        //print_r($this->layout);
        foreach ($this->layout['sections'] ?? [] as $idx => $value) {
            //print "<br/>-- Section: $idx --\n";
            //print_r($value);
            $this->add_section($idx,$value);

        };
        // foreach ($this->layout['sections'] ?? [] as $idx => $value) {
        //     $this->add_section($idx,$value);
        //     //print "-- Section: $idx --\n";
        //     //print_r($value);
        // };
        // print "</pre>\n";
        // print "<pre>\n-- Blocks --\n";
        //print_r($this->current_site['definition']['blocks']);
        // print "\n</pre>\n";
        //print_r($this->blocks);exit;
    }
    public function render($app = false)
    {
        $content = "";
        //print "-- Rendering page with layout: $this->layout_name --><pre>\n";
        //print_r($this->sections);
        $sections = $this->config_object->get('site.sections', []);
        foreach ($sections as $idx => $current_section) {
            //print "-- Rendering section: $idx -->\n";
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
