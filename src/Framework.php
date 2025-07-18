<?php

namespace Opensitez\Simplicity;

class Framework extends Base
{
    private $components = [];
    private $registry = [];
    private $core_components = ['Page',"Block","Section","Palette"];

    function __construct($config_object = null)
    {
        parent::__construct($config_object);

        if(is_array($config_object)) {
            $config_object = new \Opensitez\Simplicity\Config($config_object);
            // print_r($config_object->get("system"));
            // print "Config object created from array\n<br/>";
        } else if (!$config_object) {
            //print "No config object provided, creating default\n<br/>";
            print_r($config_object->get("system"));
            $config_object = new \Opensitez\Simplicity\Config();
        } else if ($config_object instanceof \Opensitez\Simplicity\Config) {
            //print "Using provided config object\n<br/>";
            //print_r($config_object->get("system"));

            $this->config_object = $config_object;
        } else {
            throw new \InvalidArgumentException("Invalid config object provided. Expected an instance of Opensitez\\Simplicity\\Config or an array.");
        }

        $this->on_init();
    }
    function on_init()
    {
        if (!$this->config_object) {
            $this->config_object = new \Opensitez\Simplicity\Config($default_config);
        }
        $this->load_default_components();
    }
    function getConfigObject()
    {
        return $this->config_object;
    }
    function on_event($event)
    {
        foreach ($this->components ?? [] as $group => $group_components) {
            foreach ($group_components as $name => $component) {
                if ($component->enabled) {
                    $component->on_event($event);
                }
            }
        }
    }
    function on_action($action)
    {   
        foreach ($this->components ?? [] as $group => $group_components) {
            foreach ($group_components as $name => $component) {
                if ($component->enabled) {
                    $component->on_action(["p" => $name, "a" => $action]);

                }
            }
        }
    } 
    function set_handler($component_handler)
    {
        foreach ($this->components ?? [] as $group => $group_components) {
            foreach ($group_components as $name => $component) {
                if ($component) {
                    $component->set_handler($component_handler);
                }
            }
        }
    }
    function on_render_page()
    {
        $render_component = "routing";

 
        if (!isset($no_template_output)) {
            $render = $this->components[$render_component] ?? false;
            echo $render->show_page();
        }
    }
    function load_default_components()
    {
        // Load components from the default library components folder into 'core' group
        $default_components_path = __DIR__ . '/components';
        foreach($this->core_components as $component) {
            $new_component_name = 'Opensitez\\Simplicity\\' . $component;
            $instance = new $new_component_name($this->config_object);
            $instance->set_handler($this);
            $this->components['core'][strtolower($component)] = $instance;
            if (method_exists($instance, 'on_event')) {
                $instance->on_event(['type' => MSG::onComponentLoad]);  
            }
        }
        if (is_dir($default_components_path)) {
            $this->load_components($default_components_path, 'Opensitez\\Simplicity\\Components', 'core');
        }
    }
    function load_components($curpath, $namespace = 'Opensitez\\components', $group = 'local')
    {
        if (!is_dir($curpath)) {
            return false;
        }

        if (!isset($this->components[$group])) {
            $this->components[$group] = [];
        }

        $file_list = scandir($curpath);

        foreach ($file_list ?? [] as $file) {
            if ($file === "." || $file === "..") {
                continue;
            }

            $component_dir = $curpath . DIRECTORY_SEPARATOR . $file;
            $component_file = $component_dir . DIRECTORY_SEPARATOR . $file . '.php';

            if (is_dir($component_dir) && file_exists($component_file)) {
                try {
                    include_once $component_file;
                    $classname = $namespace . '\\' . $file;

                    if (class_exists($classname)) {
                        $component_instance = new $classname($this->config_object);
                        $this->components[$group][strtolower($file)] = $component_instance;
                        $component_instance->set_handler($this);
                        if ($component_instance && method_exists($component_instance, 'on_event')) {
                            $component_instance->on_event(['type' => MSG::onComponentLoad]);
                        }
                    } else {
                        echo "Class $classname not found in $component_file\n<br/>";
                    }
                } catch (Exception $e) {
                    echo "Error loading component $file: " . $e->getMessage() . "\n<br/>";
                }
            }
        }
        return true;
    }
    function initialize_components()
    {
        foreach ($this->components as $group => $group_components) {
            foreach ($group_components as $name => $component) {
                if ($component && method_exists($component, 'on_event')) {
                    $component->on_event(['type' => MSG::onComponentLoad]);
                }
            }
        }
    }
    function load_external_components($component_paths = [])
    {
        foreach ($component_paths as $path_config) {
            $path = $path_config['path'] ?? '';
            $namespace = $path_config['namespace'] ?? 'Opensitez\\Components';
            $group = $path_config['group'] ?? 'local';

            if ($path && is_dir($path)) {
                //echo "Loading external components from: $path with namespace: $namespace into group: $group\n<br/>";
                $this->load_components($path, $namespace, $group);
            }
        }
    }
    function getPaths()
    {
        if (!$this->config_object) {
            $this->on_init();
        }
        return $this->config_object.get('paths');
    }
    function getSite()
    {
        if (!$this->config_object) {
            $this->on_init();
        }
        return $this->config_object->get('site');
    }
    function register_type($type, $key, $component_name = null)
    {
        // If no component name provided, use the calling component
        if (!$component_name) {
            // Find which component is calling this by looking at the backtrace
            $backtrace = debug_backtrace();
            foreach ($this->components as $group => $group_components) {
                foreach ($group_components as $name => $component) {
                    if (isset($backtrace[1]['object']) && $backtrace[1]['object'] === $component) {
                        $component_name = "$group.$name";
                        break 2;
                    }
                }
            }
        }
        if (!isset($this->registry[$type])) {
            $this->registry[$type] = [];
        }

        $this->registry[$type][$key] = ['component' => $component_name];
    }
    function get_registered_type($type, $key)
    {
        if (isset($this->registry[$type][$key])) {
            $component_name = $this->registry[$type][$key]['component'];
            return $this->get_component($component_name);
        }
        return null;
    }
    function get_registered_type_list($type)
    {
        return $this->registry[$type] ?? [];
    }
    function get_component($name)
    {
        // Check if name contains dot notation (group.component)
        if (strpos($name, '.') !== false) {
            list($group, $component_name) = explode('.', $name, 2);
            if (isset($this->components[$group][$component_name])) {
                return $this->components[$group][$component_name];
            }
            return false;
        }
        
        // No dot notation - search through all groups in order: core, local, then others
        $search_order = ['core', 'local'];
        
        // Add any other groups to the search order
        foreach (array_keys($this->components) as $group) {
            if (!in_array($group, $search_order)) {
                $search_order[] = $group;
            }
        }
        
        // Search through groups in order
        foreach ($search_order as $group) {
            if (isset($this->components[$group][$name])) {
                return $this->components[$group][$name];
            }
        }
        
        return false;
    }

    function gen_menu()
    {
        $newmenu = "";
        $menu = [];
        //Gather Menus from components
        foreach ($this->components as $group => $group_components) {
            foreach ($group_components as $name => $component) {
                foreach ($component->get_menus() as $menuname => $tmpmenu) {
                    if (!isset($menu[$menuname])) {
                        $menu[$menuname] = $tmpmenu;
                    } else {
                        $menu[$menuname] = array_replace_recursive($menu[$menuname], $tmpmenu);
                    }
                }
            }
        }
        $menu = weight_sort($menu);
        $newmenu = [];
        foreach ($menu as $menuid => $menudetails) {
            $link = '#';
            if (isset($menudetails['url'])) {
                $link = $menudetails['url'];
            } elseif (isset($menudetails['component'])) {
                $link = $this->gen_link($menudetails);
            }
            $newmenu[$menuid] = [
                "text" => $menudetails['text'],
                "weight" => $menudetails['weight'],
                "url" => $link,
                "visible" => $menudetails['visible'] ?? false,
                "values" => []
            ];
            foreach ($menudetails['children'] as $childid => $childmenu) {

                if (isset($childmenu['url'])) {
                    $childtext = $childmenu['text'];
                    $childlink = $childmenu['url'];
                } elseif (isset($childmenu['component'])) {
                    $query = [
                        'component' => $childmenu['component'],
                        'page' => $childmenu['page'] ?? "",
                    ];
                    if ($childmenu['action'] ?? "") {
                        $query['action'] = $childmenu['action'];
                    }
                    $childlink = "?" . http_build_query($query);
                }
                $childtext = $childmenu['text'] ?? "";
                $childentry = [
                    "text" => $childtext,
                    "url" => $childlink,
                ];
                $newmenu[$menuid]['values'][$childid] = $childentry;
            }
        }
        return $newmenu;
    }
    function gen_link($menudetails)
    {
        $query = [
            'action' => $menuentry['action'] ?? "",
            'page' => $menuentry['page'] ?? "",
        ];
        $link = http_build_query($query);
        foreach ($menudetails['children'] as $childid => $childmenu) {

            if (isset($childmenu['url'])) {
                $childtext = $childmenu['text'];
                $childlink = $childmenu['url'];
            } elseif (isset($childmenu['component'])) {
                $query = [
                    'component' => $childmenu['component'],
                    'page' => $childmenu['page'] ?? "",
                ];
                if ($childmenu['action'] ?? "") {
                    $query['action'] = $childmenu['action'];
                }
                $childlink = "?" . http_build_query($query);
            }
        }
    }
}
function weight_sort($sort_arr, $field = 'weight')
{
    uasort($sort_arr, function ($item1, $item2) {
        return $item1['weight'] ?? 0 <=> $item2["weight"] ?? 0;
    });
    return $sort_arr;
}
