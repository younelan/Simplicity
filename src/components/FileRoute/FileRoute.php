<?php
namespace Opensitez\Simplicity\Components;

use Opensitez\Simplicity\MSG;

class FileRoute extends \Opensitez\Simplicity\Component
{
    function on_event($event)
    {
        switch ($event["type"]) {
            case MSG::onComponentLoad:
                $this->framework->register_type("routeprovider", "fileroute");
                break;
            case MSG::onParseRoute:
                $this->parseRoute();
                break;
        }
        return parent::on_event($event);
    }

    /**
     * Parse the current route from the path and set route information
     */
    public function parseRoute(): void
    {
        $siteConfig = $this->config_object->get("site", []);
        $currentPath = $siteConfig["path"] ?? "";
        $definition = $siteConfig["definition"] ?? [];
        $routes = $definition["routes"] ?? [];
        //print "--fs-ee$currentPath";exit;
        $pathSegments = $currentPath
            ? explode("/", trim($currentPath, "/"))
            : [];
        //print_r($currentPath);exit;

        $defaultRoute = $definition["default-route"] ?? 
                       $this->config_object->get("defaults.default-route", "default");
        
        // print "<strong>Debug FileRoute:</strong> Current path: '$currentPath'<br/>";
        // print "<strong>Debug FileRoute:</strong> Path segments: " . implode(", ", $pathSegments) . "<br/>";
        // print "<strong>Debug FileRoute:</strong> Default route: '$defaultRoute'<br/>";
        if (empty($pathSegments)) {
            // Empty path - use default route
            $potentialRoute = $defaultRoute;
            $this->debug("<strong>Debug FileRoute:</strong> Empty path, setting potential route to '$potentialRoute'<br/>");
            //$this->config_object->set("site.route", $defaultRoute);
            $this->config_object->set("site.segments", []);
            $this->config_object->set("site.path", "");
            
            if (isset($routes[$defaultRoute])) {
                $this->config_object->set("site.current-route", $routes[$defaultRoute]);
                $this->config_object->set("site.route-found", true);
            }
        } else {
            // Get the first segment as potential route
            $potentialRoute = $pathSegments[0];
            $this->debug("<strong>Debug FileRoute:</strong> Path segments found, setting potential route to '$potentialRoute'<br/>");
            // Check if this route exists in definition.routes
            if (isset($routes[$potentialRoute])) {
                $this->debug("<strong>Debug FileRoute:</strong> Route '$potentialRoute' found in definition<br/>");
                // Found exact match in routes
                $this->config_object->set("site.route-found", true);

                $current_route = $routes[$potentialRoute];
                $current_route["route"] = $potentialRoute;
                $current_route["source"] = "config";
                $current_route["path"] = implode("/", array_slice($pathSegments, 1));
                $current_route["segments"] = array_slice($pathSegments, 1);
                $this->config_object->set("site.current-route", $routes[$potentialRoute]);
                
                // Remove the route from segments to get remaining path
                $remainingSegments = array_slice($pathSegments, 1);
                $remainingPath = implode("/", $remainingSegments);

                $this->config_object->set("site.route", $potentialRoute);
                $this->config_object->set("site.segments", $remainingSegments);
                $this->config_object->set("site.path", $remainingPath);
            } else {
                // Route not found in definition, don't set anything yet - let other providers handle it
                // Just store the potential route for other providers
            }
        }
        
        // Store potential route for next providers
        $this->config_object->set("site.potential-route", $potentialRoute);
    }
}
