<?php

namespace Opensitez\Components;
class Gallery extends \Opensitez\Cms\Component
{
    function old__construct(&$config)
    {
        $this->config = &$config;
        $this->router = &$config["router"];
        $route = '/^\/gallery\/(\w+)\/(\d+)\/?$/';
        $this->router->route($route, array($this, "on_route"));
    }
    function on_route($params)
    {
        parent::on_route($params);
        switch ($params['action'] ?? "") {
            case "view-photo":
                print "What a wonderful world {$params['id']}";
                break;
            default:
                header('HTTP/1.0 404 Not found');
                $this->config["page"]["blocks"][] = "Route Not Found";
                print "Route Not Found";
                exit;
        }
    }
}
