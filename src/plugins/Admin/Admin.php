<?php
namespace Opensitez\Simplicity\Plugins;
use Opensitez\Simplicity\MSG;

class Admin extends \Opensitez\Simplicity\Plugin
{
    function on_event($event)
    {
        switch ($event["type"]) {
            case MSG::onComponentLoad:
                $this->framework->register_type("routetype", "admin");
                break;
        }
        return parent::on_event($event);
    }
    function on_render_page($app)
    {
        $plugin_name = $_GET['plugin'] ?? $app['plugin'] ?? "admin";
        $app['plugin'] = $plugin_name;
        $app['page'] = $_GET['page'] ?? $app['page'] ?? "default";
        $page = $app['page'] ?? "";
        if (!$page) {
            $page = "default";
        }
        $defaults = $this->config_object->get('defaults');
        $site = $this->config_object->get('site', []);
        $route = $this->config_object->get('site.current-route', []);
        $theme_name = $defaults['admintheme'] ?? "oszadmin";
        $this->config_object->set('site.vars.theme', $theme_name);
        $this->config_object->set('site.vars.pagetitle', "Open Sites Admin");
        $plugin = $this->framework->get_component($plugin_name);

        if (!$plugin) {
            $plugin_name = "admin";
            $page = "default";
            $plugin = $this->framework->get_component($plugin_name);
        }
        if ($plugin) {
            if ($plugin_name != "admin") {
                $retval = $plugin->on_render_admin_page($app);
            } else {
                $retval = "Welcome to OpenSitez Admin Module";
            }
        }
        $rawmenu = $this->framework->gen_menu();
        $adminmenu = [ "admin" => [ "values" => $rawmenu ]  ];
        $this->config_object->set('site.vars.adminmenu', $this->render_admin_menu($rawmenu));

        $this->config_object->set('site.vars.theme', $theme_name);
        $this->config_object->set('site.data.navigation', $adminmenu);

        return $retval;
    }
    function render_admin_menu($rawmenu)
    {
        $retval = "\n\n<ul class='nav-category'>\n";
        foreach ($rawmenu as $menuid => $category) {
            $retval .= "  <li>\n";
            $categorytext = $category['text'] ?? "";
            $retval .= "    <span class='nav-category-title' onclick='toggleCategory(this)'>" . $categorytext . "</span>\n";
            if ($category['visible'] ?? false) {
                $retval .= "    <ul class='nav-category-items active'>\n";
            } else {
                $retval .= "    <ul class='nav-category-items'>\n";
            }
            foreach ($category['values'] ?? [] as $submenu) {
                $sublink = $submenu['url'];
                $subtext = $submenu['text'];
                $retval .= "        <li><a class='nav-link' href='$sublink'>$subtext</a></li>\n";
            }
            $retval .= "    </ul>\n";
            $retval .= "  </li>\n";
        }
        $retval .= "</ul>";
        return $retval;
    }
    // function get_menus($app = [])
    // {
    //     $menus = [
    //         "admin" => [
    //             "text" => "Admin",
    //             "weight" => 0,

    //             "children" => [
    //                 "sites" => ["plugin" => "admin", "page" => "sites", "text" => "Active Sites", "category" => "all"],
    //                 "users" => ["plugin" => "admin", "page" => "users", "text" => "User Management   ", "category" => "all"],
    //                 "logoff" => ["plugin" => "admin", "page" => "default", "text" => "Logoff", "category" => "all"],
    //             ],
    //             "weight" => 1,
    //         ],

    //     ];
    //     return $menus;
    // }
}
