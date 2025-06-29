<?php

namespace Opensitez\Simplicity;

enum MSG
{
    case Init;
    case ParseSite;
    case RenderPage;
    case RenderBlock;
    case Shutdown;
    case RegisterTemplateEngine;
    case RegisterDirective;
    case Authenticate;
    case RouteNotFound;
    case Error;
    case PluginLoad;
}
class Plugin extends Base
{
    public $name;
    public $description;
    public $enabled = true;
    protected $plugins = false;
    protected $config = [];
    protected $app = null;
    protected $config_object = null;
    protected $debug = null;

    function __construct($config_object = null)
    {
        $this->config_object = $config_object;
    }
    function str_replace_once($search, $replace, $subject)
    {
        $pos = strpos($subject, $search);
        if ($pos !== false) {
            return substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }
    function base_url()
    {
        $paths = $this->config_object->get('paths');
        return $paths['sitepath'];
    }
    function get_config($key) {
        if( isset($this->config_object) && $this->config_object ) {
            return $this->config_object->get($key);
        } else {
            return null;
        }
    }
    

    function anchor($url, $param, $rel = 'rel=external')
    {
        $webroot = $this->get_config('paths.webroot');
        $url = "$webroot/" . $url;

        $url = "<a href='" . $url . "'>" . $param . "</a>";
        return $url;
    }
    function absolute_link($url) {
        $paths = $this->config_object->get('paths');
        if (strpos($url, 'http') === 0) {
            return $url; // Already an absolute URL
        }
        if (strpos($url, '/') === 0) {
            return $paths['webroot'] . $url; // Absolute path
        }
        return $paths['webroot'] . '/' . $url; // Relative path
    }

    function on_event($event)
    {
        switch ($event["type"]) {
            case MSG::Init:
                break;
            case MSG::ParseSite:
                break;
            case MSG::RenderPage:
                break;
            case MSG::Shutdown:
                break;
            case MSG::Error:
                break;
            case MSG::PluginLoad:
                // Override in child classes to handle plugin registration
                break;
        }

        return false;
    }
    function generate_input($data)
    {
        $form = $this->get_plugin("form");
        return $form->generate_input($data);
    }
    function set_handler(&$handler)
    {
        $this->plugins = &$handler;
    }
    function on_plugin_load()
    {
        // Called when plugin is loaded, override in child classes
        // Use this to register services, content providers, etc.
        // DEPRECATED: Use on_event(['type' => MSG::PluginLoad]) instead
    }
    function get_route_types()
    {
        $name = $this->name;
        if (!$name) {
            $name = get_class($this);
        }
        $types = [
            strtolower(get_class($this)) => [
                'name' => $name,
            ]
        ];

        return $types;
    }

    function getDebug()
    {
        if (!$this->debug) {
            $this->debug = $this->config_object->getDebugObject();
        }
        return $this->debug;
    }
    
    function get_plugin($plugin_name)
    {
        $plugins = $this->plugins;
        return $plugins->get_plugin($plugin_name);
    }
    function set_app($app)
    {
        $this->app = $app;
    }
    public function get_palette($app)
    {
        $palette = new Palette($this->config_object);
        $palette->set_handler($this->plugins);
        $palette = $palette->render($app);
        return $palette;
    }
    function render($current)
    {
        if (!$current) {
            $current = $this->current;
        }
        $content = "";
        return $content;
    }
    function on_action($app)
    {
    
    }
    function on_render_page($app)
    {
        if (!$app) {
            $current = $this->app;
        }
        $content = "";
        return $content;
    }
    function on_render_admin_page($app)
    {
        return "";
    }
    function SendMessage($message)
    {
        $this->plugins->SendMessage($message);
    }
    function on_site_definition($params)
    {
        return true;
    }
    function retrieve($fname = null)
    {
        $paths = $this->config_object->get('paths');
        if ($fname) {
            $cfg = $paths['data'] . "/$fname";
        } else {
            $classname = strtolower(get_class($this));
            $cfg = $paths['data'] . "/$classname.json";
        }
        $contents = @file_get_contents($cfg);
        $contents = @json_decode($contents, true);
        return $contents;
    }
    function store($collection, $fname = null)
    {
        $paths = $this->config_object->get('paths');
        $json = json_encode($collection, JSON_PRETTY_PRINT);
        if ($fname) {
            $cfg = $paths['data'] . "/$fname";
        } else {
            $classname = get_class($this);
            $cfg = $paths['data'] . "/$classname";
        }
        file_put_contents($cfg, $json);
    }
    function getConfigObject()
    {
        return $this->config_object;
    }

    /*substitute vars and placeholders should be one really eventually */
    // function replace_placeholders($content, $data)
    // {
    //     foreach ($data as $key => $value) {
    //         $placeholder = '{' . $key . '}';
    //         $content = str_replace($placeholder, $value, $content);
    //     }

    //     return $content;
    // }
}
