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
    protected $framework = false;
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
    
    public function add_section($idx, $new_section=[])
    {
            $section_options = $this->config_object->get('site.sections.' . $idx, false);
            if(!$section_options) {
                $value['name'] = $idx;
                $section_options = $new_section;
                $new_section = new Section($this->config_object);
                $new_section->set_handler($this->framework);
                $new_section->set_section_options($section_options);
                $this->config_object->set('site.sections.' . $idx, $new_section);
            }
            return $new_section;
    
    }
    public function valid_var_name($block_name)
    {
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9-]*$/', $block_name)) {
            return true;
        } else {
            
            return false;
        }
    }
    public function add_block($block)
    {
        if (!$this->valid_var_name($block['name'])) {
            print "Invalid block name: " . $block['name'] . " (must match pattern [a-zA-Z][a-zA-Z0-9-]*)\n";
            return;
        }
        $defaults = $this->config_object->get('defaults');
        $block['type'] = $block['type'] ?? 'text';
        $block['content'] = $block['content'] ?? '';
        $block['content-type'] = $block['content-type'] ?? 'html';
        $block['template'] = $block['template'] ?? 'default.tpl';

        $block_name = $block['name'] ;
        $section = $block['section'] ?? $defaults['section'] ?? "content";
        if (!$this->valid_var_name($section)) {
            print "Invalid section name: " . $section . " (must match pattern [a-zA-Z][a-zA-Z0-9-]*)\n";
            $section = 'content';
        }
        $block['section'] = $section;

        if (!$this->config_object->get('site.sections.' . $section)) {
            $this->add_section($section);
        }

        $block_instance = new Block($this->config_object);
        print "Adding block: " . $block_name . " to section: " . $section . "\n";
        $block_instance->set_block_options($block);
        $block_instance->set_handler($this->framework);

        $this->config_object->set("site.blocks.$block_name", $block_instance);
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
        $form = $this->get_component("form");
        return $form->generate_input($data);
    }
    function set_handler(&$handler)
    {
        $this->framework = &$handler;
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
    
    function get_component($plugin_name)
    {
        $plugins = $this->framework;
        return $plugins->get_component($plugin_name);
    }
    function set_app($app)
    {
        $this->app = $app;
    }
    public function get_palette($app)
    {
        $palette = new Palette($this->config_object);
        $palette->set_handler($this->framework);
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
        $this->framework->SendMessage($message);
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
