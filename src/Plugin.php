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
    function base_url()
    {
        $paths = $this->config_object->getPaths();
        return $paths['sitepath'];
    }

    function anchor($url, $param, $rel = 'rel=external')
    {
        return "<a href=\"/" . $url . "\">" . $param . "</a>";
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
    function get_menus()
    {
        $menus = [
            // "menuname" => [
            //     "text"=>"Menu name",
            //     "weight"=> 0,
            //     "children"=> [
            //        "menuentry"=> ["plugin"=>"gallery","page"=>"pageid","text"=>"Menu Text","category"=>"all"],
            //     ]
            // ],

        ];
        return $menus;
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
    function on_render_block($current)
    {
        if (!$current) {
            $current = $this->current;
        }
        $content = "";
        return $content;
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
    function getConfigObject()
    {
        return $this->config_object;
    }

    /*substitute vars and placeholders should be one really eventually */
    function replace_placeholders($content, $data)
    {
        foreach ($data as $key => $value) {
            $placeholder = '{' . $key . '}';
            $content = str_replace($placeholder, $value, $content);
        }

        return $content;
    }
    function substitute_vars($text, $arrays)
    {
        foreach ($arrays as $vararray) {
            foreach ($vararray ?? [] as $tplvar => $tplvalue) {
                if (is_string($tplvalue)) {
                    $tplvar = '{{$' . $tplvar . "}}";
                    $text = str_replace($tplvar, $tplvalue, $text);
                }
            }
        }
        return $text;
    }
}
