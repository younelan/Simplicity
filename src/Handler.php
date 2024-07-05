<?php

namespace Opensitez\Simplicity;

class Handler
{
    private $plugins = [];
    private $config_object = null;

    function on_init($config_object = null)
    {
        if ($config_object) {
            $this->config_object = $config_object;
        } else {
            $this->config_object = new \Opensitez\Plugins\Config();
        }
        $this->config_object->on_init();
        // $this->config_object=$config_object;
        $this->config = $this->config_object->getLegacyConfig();
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
    function load_plugins($curpath)
    {
        $config_object = $this->getConfigObject();
        $file_list = scandir($curpath);
        foreach ($file_list ?? [] as $file) {
            $plugin_path = $curpath . "/$file/$file.php";
            if ($file != "." && $file != "..") {
                //print "Trying to load $file/$file.php\n<br/>";

                if (is_dir($curpath . "/$file")) {
                    if (file_exists($curpath . "/$file/$file.php")) {

                        if (is_file($plugin_path)) {
                            //print "loaded $plugin_path\n<br/>";
                            include_once("$plugin_path");
                            $classname = "\\Opensitez\\Plugins\\$file";
                            if (class_exists($classname)) {
                                $this->plugins[strtolower($file)] = new $classname($this->config_object);
                                $this->plugins[strtolower($file)]->set_handler($this);
                            }
                        } else {
                            print("Not a plugin $file\n");
                        }
                    }
                }
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
