<?php

namespace Opensitez\Simplicity\Components;

use Opensitez\Simplicity\MSG;

class Redirect extends \Opensitez\Simplicity\Component
{
    public $name = "Redirect";
    public $description = "URL Redirection/ Shortener Component";
    
    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::onComponentLoad:
                // Register this component as a route type handler for redirects
                $this->framework->register_type('routetype', 'redirect');
                $this->framework->register_type("routeprovider", "redirect");
                break;
        }
        return parent::on_event($event);
    }
    public function parseRoute(): void
    {
        $redirects = $this->config_object->get('site.definition.redirects') ?? [];
        foreach ($redirects as $route_name => $url) {
            $new_route = [
                'type' => 'redirect',
                'url' => $url ?? '/',
                'code' => 301,
            ];
            $this->add_route($route_name , $new_route);
        }

    }  

    function get_menus($app = [])
    {
        $menus = [
            "content" => [
                "text" => "Content",
                "image" => "genimgwebsite1.png",
                "children" => [
                    "redirects" => ["component" => "redirects", "page" => "default", "text" => "Redirects", "category" => "all"],
                ]
            ],

        ];
        return $menus;
    }
    public function render_page($app)
    {
        //print_r($app);exit;
        $url = $app['url'] ?? "/";
        $code = $app['code'] ?? 301;
        header("Location: $url", true, $code);
        print("Location: $url " . $code);
        exit();
    }
    public function on_render_page($app)
    {
        $output = $this->render_page($app);
        return $output;
    }
    public function get_html()
    {
        return $this->render_page();
    }
}
