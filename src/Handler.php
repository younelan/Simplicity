<?php

namespace Opensitez\Simplicity;

class Handler
{
    private $plugins = [];
    private $config_object = null;
    private $registry = [];

    function on_init($config_object = null)
    {
        if ($config_object) {
            $this->config_object = $config_object;
        } else {
            $this->config_object = new \Opensitez\Simplicity\Config();
        }
        // Load default plugins from library
        $this->load_default_plugins();
        
        // Second pass: call on_plugin_load for all loaded plugins
        $this->initialize_plugins();
    }
    function getConfigObject()
    {
        if (!$this->config_object) {
            $this->on_init();
        }
        return $this->config_object;
    }
    function on_event($event)
    {
        foreach ($this->plugins ?? [] as $name => $plugin) {
            //print $plugin->name . " - $name <br/>";
            if ($plugin->enabled) {
                $plugin->on_event($event);
            } else {
                //print("$name not enabled");
            }
        }
    }
    function set_handler($plugin_handler)
    {
        foreach ($this->plugins ?? [] as $name => $plugin) {
            if ($plugin) {
                $plugin->set_handler($plugin_handler);
            }
        }
    }
    function on_render_page()
    {
        $render_plugin = "routing";


        if (!isset($no_template_output)) {
            $render = $this->plugins[$render_plugin] ?? false;
            echo $render->show_page();
        }
    }
    function load_default_plugins()
    {
        // Load plugins from the default library plugins folder
        $default_plugins_path = __DIR__ . '/plugins';
        if (is_dir($default_plugins_path)) {
            $this->load_plugins($default_plugins_path, 'Opensitez\\Simplicity\\Plugins');
        }
    }

    function load_plugins($curpath, $namespace = 'Opensitez\\Plugins')
    {
        if (!is_dir($curpath)) {
            return false;
        }

        $config_object = $this->getConfigObject();
        $file_list = scandir($curpath);

        foreach ($file_list ?? [] as $file) {
            if ($file === "." || $file === "..") {
                continue;
            }

            $plugin_dir = $curpath . DIRECTORY_SEPARATOR . $file;
            $plugin_file = $plugin_dir . DIRECTORY_SEPARATOR . $file . '.php';

            if (is_dir($plugin_dir) && file_exists($plugin_file)) {
                try {
                    include_once $plugin_file;
                    $classname = $namespace . '\\' . $file;

                    if (class_exists($classname)) {
                        $plugin_instance = new $classname($this->config_object);
                        $this->plugins[strtolower($file)] = $plugin_instance;
                        $plugin_instance->set_handler($this);

                        echo "Loaded plugin: $file from namespace: $namespace\n<br/>";
                    } else {
                        echo "Class $classname not found in $plugin_file\n<br/>";
                    }
                } catch (Exception $e) {
                    echo "Error loading plugin $file: " . $e->getMessage() . "\n<br/>";
                }
            }
        }

        return true;
    }

    function initialize_plugins()
    {
        // Second pass: send plugin-load event to all loaded plugins
        $this->on_event(['type' => MSG::PluginLoad]);
    }

    function load_external_plugins($plugin_paths = [])
    {
        foreach ($plugin_paths as $path_config) {
            $path = $path_config['path'] ?? '';
            $namespace = $path_config['namespace'] ?? 'Opensitez\\Plugins';

            if ($path && is_dir($path)) {
                echo "Loading external plugins from: $path with namespace: $namespace\n<br/>";
                $this->load_plugins($path, $namespace);
            }
        }
    }

    function getPaths()
    {
        if (!$this->config_object) {
            $this->on_init();
        }
        return $this->getConfigObject()->getPaths();
    }
    
    function register($type, $key, $plugin_name = null)
    {
        // If no plugin name provided, use the calling plugin
        if (!$plugin_name) {
            // Find which plugin is calling this by looking at the backtrace
            $backtrace = debug_backtrace();
            foreach ($this->plugins as $name => $plugin) {
                if (isset($backtrace[1]['object']) && $backtrace[1]['object'] === $plugin) {
                    $plugin_name = $name;
                    break;
                }
            }
        }
        
        if (!isset($this->registry[$type])) {
            $this->registry[$type] = [];
        }
        
        $this->registry[$type][$key] = ['plugin' => $plugin_name];
        echo "Registered $type: $key -> $plugin_name\n<br/>";
    }
    
    function get_registered($type, $key)
    {
        if (isset($this->registry[$type][$key])) {
            $plugin_name = $this->registry[$type][$key]['plugin'];
            return $this->get_plugin($plugin_name);
        }
        return null;
    }
    
    function get_registered_list($type)
    {
        return $this->registry[$type] ?? [];
    }
    
    function get_plugin($name)
    {
        // foreach(array_keys($this->plugins) as $key) {
        //   print $key . "<br>";
        // }
        if (isset($this->plugins[$name])) {
            return $this->plugins[$name];
        } else {
            return false;
        }
    }
    function get_route_types()
    {
        $types = [];
        foreach ($this->plugins as $name => $plugin) {
            $route_types = $plugin->get_route_types();
            if ($route_types) {
                foreach ($route_types as $route_name => $route_type) {
                    $types[$route_name] = $route_type['name'];
                }
            }
            $types[$name] = $route_type['name'];
        }
        return $types;
    }
    function gen_menu()
    {
        $newmenu = "";
        $menu = [];
        //Gather Menus from plugins
        foreach ($this->plugins as $name => $plugin) {
            foreach ($plugin->get_menus() as $menuname => $tmpmenu) {
                if (!isset($menu[$menuname])) {
                    $menu[$menuname] = $tmpmenu;
                } else {
                    $menu[$menuname] = array_replace_recursive($menu[$menuname], $tmpmenu);
                }
            }
        }
        $menu = weight_sort($menu);
        $newmenu = [];
        foreach ($menu as $menuid => $menudetails) {
            $link = '#';
            if (isset($menudetails['url'])) {
                $link = $menudetails['url'];
            } elseif (isset($menudetails['plugin'])) {
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
                } elseif (isset($childmenu['plugin'])) {
                    $query = [
                        'plugin' => $childmenu['plugin'],
                        'page' => $childmenu['page'] ?? "",
                    ];
                    if ($childmenu['action'] ?? "") {
                        $query['action'] = $childmenu['action'];
                    }
                    $childlink = "?" . http_build_query($query);
                }

                //$childtext=get_translation($childmenu['text']??"");
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
            } elseif (isset($childmenu['plugin'])) {
                $query = [
                    'plugin' => $childmenu['plugin'],
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
    //print_r($sort_arr);
    //exit;
    return $sort_arr;
}
