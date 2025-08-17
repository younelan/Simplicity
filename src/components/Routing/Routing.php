<?php

namespace Opensitez\Simplicity\Components;
use Opensitez\Simplicity\MSG;

class Routing extends \Opensitez\Simplicity\Component
{
    public $name = "Routing";
    public $description = "Site routing and domain resolution";


    function on_event($event)
    {
        switch ($event["type"]) {
            case MSG::onComponentLoad:
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
        $events = [
            ["type" => MSG::onParseSite, "domain" => $domain, "providerType" => "siteprovider"],
            ["type" => MSG::onParseRoute, "providerType" => "routeprovider"],
            ["type" => MSG::onAuth, "providerType" => "authprovider"],
            ["type" => MSG::onSetPalette, "providerType" => "paletteprovider"],
            ["type" => MSG::onSetLayout, "providerType" => "layoutprovider"],
            ["type" => MSG::onSetBlocks, "providerType" => "blockprovider"],
            ["type" => MSG::onSetMenus, "providerType" => "menuprovider"],
            ["type" => MSG::onRenderPage, "providerType" => "pageprovider"],
            ["type" => MSG::onShutdown, "providerType" => "shutdownlistener"]
        ];

        foreach ($events as $event) {
            $type = $event["type"];
            $providerType = $event["providerType"] ?? null;
            $components = [];
            if ($providerType) {
                $components = $this->framework->get_registered_type_list($providerType);
            }
            foreach ($components as $provider => $details) {
                $component = $this->framework->get_component($provider);
                if (method_exists($component, "on_event")) {
                    $eventData = ["type" => $type];
                    if ($type === MSG::onParseSite && isset($event["domain"])) {
                        $eventData["domain"] = $event["domain"];
                    }
                    $component->on_event($eventData);
                    if ($type === MSG::onParseRoute) {
                        $this->debug("<strong>Debug:</strong> After '$provider', site.route = '" . $this->config_object->get("site.route", "not set") . "'<br/>");
                        $this->debug("<strong>Debug:</strong> site.current-route = ");
                        $this->debug(print_r($this->config_object->get("site.current-route", []), true));
                        $this->debug("<br/>");
                    }
                } else {
                    $this->debug("<strong>Debug:</strong> Component '$provider' does not have on_event method<br/>");
                }
            }
        }
        // $palette_plugin->setPalette();
        return $this->config_object->get("site");
    }
    /**
     * Legacy method to check authentication, kept until all references are updated.
     * @deprecated Use Auth Event instead
     */
    function legacyCheckAuth()
    {
        //$auth = $this->framework->get_plugin("page");
        $auth = new Auth($this->config_object);
        $auth->set_handler($this->framework);
        //print_r($this->auth);exit;
        $app = $this->app;
        //print_r($app);exit;
        if ($app['auth'] ?? false) {
            $auth_type = $app['auth']['type'];
            if ($auth_type == 'simple') {
                $user = $auth->require_login();
                $user = $auth->get_user();
                $config['user'] = $user;
                if ($user) {
                } else {
                    exit;
                }
            } else {
                die("Unsupported Auth Type");
            }
        }            
    }
    /**
     * Legacy method to show page, kept until all references are updated.
     * @deprecated Use handleRoute() instead
     */
    function legacyShowPage()
    {
        $section_object = $this->framework->get_plugin("section");
        return $section_object->render_block_list($inserts, $app);
        
        $this->app = $this->config_object->getApp();
        $current_site = $this->config_object->getCurrentSite();

        $page = $this->framework->get_plugin('page');
        $this->check_auth();

        $page_before  = $this->current_site['before'] ?? [];
        $page_after  = $this->current_site['after'] ?? [];
        $current_plugin = $this->framework->get_plugin($this->app['type'] ?? false);
        $current_path = $this->app['route'] ?? "";
        $app_before = $this->app['before'] ?? [];

        if ($current_plugin) {
            $before = $this->app['before'] ?? [];
            $after = $this->app['after'] ?? [];
            $footer = $this->app['footer'] ?? $current_site['footer'] ?? [];
            $content = $section_object->render_block_list($before, $this->app);
            $content .= $current_plugin->on_render_page($this->app);
            $content .= $section_object->render_block_list($after, $this->app);
            $content .= $section_object->render_block_list($footer, $this->app);
            $this->config_object->setVar('content', $content);
        }
        if (!isset($no_template_output)) {
            $template = $this->framework->get_plugin("template");
            return $template->on_render_page($this->app);
        } else {
            return false;
        }
    }
    public function handleRoute(): string
    {
        $siteConfig = $this->config_object->get("site", []);
        $routeConfig = $this->config_object->get("site.current-route", "");
        $route = $this->config_object->get("site.route", "");
        $definition = $siteConfig["definition"] ?? [];
        $routes = $definition["routes"] ?? [];
        $debug = $this->framework->get_component("debug");
        // $this->debug($debug->printArray($siteConfig));
        // $this->debug(print_r($siteConfig['current-route'], true));

        if (!$routeConfig) {
            $this->debug("<strong>Debug:</strong> No route or route '$route' not found in definition<br/>");
            return "";
        }
        $routeType =
            $routeConfig["type"] ??
            $this->config_object->get("defaults.route-type", "page");

        if (!$routeType) {
            $this->debug("<strong>Debug:</strong> No route type specified for route '$route'<br/>");
            return "";
        }
        $routeHandler = $this->framework->get_registered_type(
            "routetype",
            $routeType
        );
        if (!$routeHandler) {
            $this->debug("<strong>Debug:</strong> No handler registered for route type '$routeType'<br/>");
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

        $page_before  = $routeData['before'] ?? [];
        $page_after  = $routeData['after'] ?? [];

        $section_object = $this->framework->get_component("section");
        $content_before = $section_object->render_block_list($page_before, $this->app);
        $content_after = $section_object->render_block_list($page_after, $this->app);

        // print_r($content_before);
        // // print_r($page_after);
        //  exit;

        if (method_exists($routeHandler, "on_render_page")) {
            $routeHandler->set_options($routeData);
            $content = $content_before . $routeHandler->on_render_page($routeData) . $content_after;
            $this->config_object->set("site.current-route", $routeData);
        } else {
            $this->debug("<strong>Debug:</strong> Handler for route type '$routeType' does not have on_render_page method<br/>");
            $content = $content_before . "" . $content_after;
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
