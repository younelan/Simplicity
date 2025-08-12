<?php
namespace Opensitez\Cms\Components;
use Opensitez\Simplicity\MSG;

require_once __DIR__     . "/FilePassthrough.php";

class ContentRoute extends \Opensitez\Simplicity\Component
{
    function on_event($event)
    {
        switch ($event["type"]) {
            case MSG::onComponentLoad:
                $this->framework->register_type("routeprovider", "contentroute");
                break;
            case MSG::onParseRoute:
                $this->parseRoute();
                break;
        }
        return parent::on_event($event);
    }
    function loadRouteFile(string $key, string $routePath): void
    {
        if (file_exists($routePath)) {
            $this->config_object->mergeYaml($key, $routePath);
        } else {
            $this->debug("<strong>Debug:</strong> Route file '$routePath' does not exist, skipping<br/>");
        }
    }

    function parseRoute()
    {
        $siteConfig = $this->config_object->get("site", []);
        $currentPath = $siteConfig["path"] ?? "";
        $definition = $siteConfig["definition"] ?? [];
        //print "<strong>Debug ContentRoute:</strong> Current path: '$currentPath'<br/>";
        $pathSegments = $currentPath
            ? explode("/", trim($currentPath, "/"))
            : [];

        $routeFound = $this->config_object->get("site.route-found", false);
        $contentFolder = $this->config_object->get("paths.site-content", "");
        $defaultRoute = $definition["default-route"] ?? 
                       $this->config_object->get("defaults.default-route", "default");
        
        // Get the potential route from FileRoute or determine it ourselves
        $potentialRoute = $this->config_object->get("site.potential-route", "");
        if (!$potentialRoute) {
            $potentialRoute = empty($pathSegments) ? $defaultRoute : $pathSegments[0];
        }

        if (!$routeFound) {
            // Check if the potential route has a .route file
            $routeFile = "$contentFolder/$potentialRoute.route";
            if (file_exists($routeFile)) {
                $this->loadRouteFile("site.definition.routes.$potentialRoute", $routeFile);
                $routeConfig = $this->config_object->get("site.definition.routes.$potentialRoute", []);
                if (!empty($routeConfig)) {
                    $routeConfig["route"] = $potentialRoute;
                    $routeConfig["path"] = implode("/", array_slice($pathSegments, 1));
                    $routeConfig["segments"] = array_slice($pathSegments, 1);
                    $this->config_object->set("site.current-route", $routeConfig);
                    $this->config_object->set("site.route", $potentialRoute);
                    $this->config_object->set("site.route-found", true);
                    if (!empty($pathSegments) && $pathSegments[0] === $potentialRoute) {
                        $remainingSegments = array_slice($pathSegments, 1);
                        $remainingPath = implode("/", $remainingSegments);
                    }
                }
            } else {
                // No .route file found for requested path, try to serve a static file
                $allowedExtensions = ['html', 'htm', 'css', 'jpg', 'jpeg', 'gif', 'png', 'js', 'txt', 'pdf', 'svg', 'ico', 'webp', 'avif', 'woff', 'woff2', 'ttf', 'otf'];
                foreach ($pathSegments as $seg) {
                    if ($seg === '' || strpos($seg, '..') !== false) {
                        $this->debug("<strong>Debug ContentRoute:</strong> Insecure segment detected: '$seg'<br/>");
                        return;
                    }
                }
                $lastSegment = end($pathSegments);
                $ext = strtolower(pathinfo($lastSegment, PATHINFO_EXTENSION));
                if (in_array($ext, $allowedExtensions)) {
                    $filePath = $contentFolder . '/' . implode('/', $pathSegments);
                    $this->debug("<strong>Debug ContentRoute:</strong> Checking file path: '$filePath'<br/>");
                    if (is_file($filePath)) {
                        // Set route-found and current-route, then serve file and exit
                        $this->config_object->set("site.route-found", true);
                        $this->config_object->set("site.current-route", [
                            'route' => $lastSegment,
                            'source' => 'file',
                            'file' => $filePath,
                            'type' => $ext
                        ]);
                        $this->debug("<strong>Debug ContentRoute:</strong> Serving file: '$filePath' with extension '$ext'<br/>");
                        $passthrough = new \Opensitez\Cms\Components\FilePassthrough();
                        $options = [
                            'filename' => $filePath,
                            'extension' => $ext,
                            'allowedExtensions' => $allowedExtensions
                        ];
                        $passthrough->render($options);
                        exit;
                    }
                }
                // If not a static file, fall back to default route
                if (file_exists("$contentFolder/$defaultRoute.route")) {
                    $routeFile = "$contentFolder/$defaultRoute.route";
                    $this->loadRouteFile("site.definition.routes.$defaultRoute", $routeFile);
                    $routeConfig = $this->config_object->get("site.definition.routes.$defaultRoute", []);
                    if (!empty($routeConfig)) {
                        $routeConfig["route"] = $defaultRoute;
                        $routeConfig["source"] = "content";
                        $routeConfig["path"] = $currentPath;
                        $routeConfig["segments"] = $pathSegments;
                        $this->config_object->set("site.current-route", $routeConfig);
                        $this->config_object->set("site.route", $defaultRoute);
                        $this->config_object->set("site.segments", $pathSegments);
                        $this->config_object->set("site.path", $currentPath);
                    }
                }
            }
        }
    }
}
