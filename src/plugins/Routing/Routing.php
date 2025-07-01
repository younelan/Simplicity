<?php

namespace Opensitez\Simplicity\Plugins;
use Opensitez\Simplicity\MSG;

class Routing extends \Opensitez\Simplicity\Plugin
{
    public $name = "Routing";
    public $description = "Site routing and domain resolution";

    function on_event($event)
    {
        switch ($event["type"]) {
            case MSG::PluginLoad:
                $this->framework->register_type("routehandler", "routing");
                break;
        }
        return parent::on_event($event);
    }
    public function getSite(): ?array
    {
        if (!$this->config_object) {
            return null;
        } 
        $domain = $this->config_object->get("site.host", "");
        $siteProviders = $this->framework->get_registered_type_list("siteprovider");
        foreach ($siteProviders as $provider=> $details) {
            $siteProvider = $this->framework->get_component($provider);
            if (method_exists($siteProvider, "checkSiteConfiguration")) {
                $siteProvider->checkSiteConfiguration($domain);
            } else {
                echo "<strong>Debug:</strong> Site provider '$provider' does not have checkSiteConfiguration method<br/>";
            }
        }
        $filesite->checkSiteConfiguration($domain);
        $palette_plugin = $this->framework->get_component("palette");

        $routeProviders = $this->framework->get_registered_type_list("routeprovider");
        foreach ($routeProviders as $provider=> $details) {
            $routeProvider = $this->framework->get_component($provider);
            if (method_exists($routeProvider, "parseRoute")) {
                $routeProvider->parseRoute();
            } else {
                echo "<strong>Debug:</strong> Route provider '$provider' does not have parseRoute method<br/>";
            }
        }
        $palette_plugin->setPalette();
        return $this->config_object->get("site");
    }
    public function handleRoute(): string
    {
        $siteConfig = $this->config_object->get("site", []);
        $routeConfig = $this->config_object->get("site.current-route", "");
        $route = $this->config_object->get("site.route", "");
        $definition = $siteConfig["definition"] ?? [];
        $routes = $definition["routes"] ?? [];

        if (!$routeConfig) {
            echo "<strong>Debug:</strong> No route or route '$route' not found in definition<br/>";
            return "";
        }
        $routeType =
            $routeConfig["type"] ??
            $this->config_object->get("defaults.route-type", "page");

        if (!$routeType) {
            echo "<strong>Debug:</strong> No route type specified for route '$route'<br/>";
            return "";
        }
        $routeHandler = $this->framework->get_registered_type(
            "routetype",
            $routeType
        );
        if (!$routeHandler) {
            echo "<strong>Debug:</strong> No handler registered for route type '$routeType'<br/>";
            return "";
        }
        $defaultRouteName = $definition["default-route"] ?? "default";
        if ($route === $defaultRouteName && isset($routes[$defaultRouteName])) {
            $routeConfig = $routes[$defaultRouteName];
        }
        $routeData = $routeConfig;
        $routeData["route"] = $route;
        $routeData["path"] = $siteConfig["path"] ?? "";
        $routeData["segments"] = $siteConfig["segments"] ?? [];
        $routeData["domain"] = $siteConfig["host"] ?? "";

        if (method_exists($routeHandler, "on_render_page")) {
            $routeHandler->set_options($routeData);
            $content = $routeHandler->on_render_page($routeData);
            $this->config_object->set("site.current-route", $routeData);
        } else {
            echo "<strong>Debug:</strong> Handler for route type '$routeType' does not have on_render_page method<br/>";
            $content = "";
        }
        if (!isset($no_template_output)) {
            $template = $this->framework->get_component("theme");
            $this->config_object->set("site.vars.content", $content);
            //$definition['vars']['content'] = $content;
            return $template->on_render_page($definition) ?? "";
        } else {
            return false;
        }

        return $content;
    }
}

// function old_show_page()
// {

//     $this->app = $this->config_object->get('site');
//     $current_site = $this->config_object->get('site');

//     $page = $this->framework->get_component('page');
//     $this->check_auth();

//     $page_before  = $this->current_site['before'] ?? [];
//     $page_after  = $this->current_site['after'] ?? [];
//     $current_plugin = $this->framework->get_component($current_site['type'] ?? false);
//     $current_path = $this->app['route'] ?? "";
//     $app_before = $this->app['before'] ?? [];

//     if ($current_plugin) {
//         $before = $this->app['before'] ?? [];
//         $after = $this->app['after'] ?? [];
//         $footer = $this->app['footer'] ?? $current_site['footer'] ?? [];
//         $content = $page->render_inserts($before, $this->app);
//         $content .= $current_plugin->on_render_page($this->app);
//         $content .= $page->render_inserts($after, $this->app);
//         $content .= $page->render_inserts($footer, $this->app);

//         $this->config_object->set('site.vars.content', $content);
//     }
//     if (!isset($no_template_output)) {
//         $template = $this->framework->get_component("template");
//         return $template->on_render_page($this->app);
//     } else {
//         return false;
//     }
// }
