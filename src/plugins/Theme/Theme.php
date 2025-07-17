<?php
namespace Opensitez\Simplicity\Plugins;

use Opensitez\Simplicity\MSG;

class Theme extends \Opensitez\Simplicity\Plugin
{
    private $template_engine;
    private $current_site;
    private $defaults;
    private $paths;
    private $default_template;
    private $default_theme;
    private $current_theme;
    private $themedir;
    private $current_template;
    private $i18n;
    private $master;
    private $theme;
    private $pagestyle;
    private $template;
    protected $charset;
    protected $app;
    protected $site;
    protected $themepath;
    function init_paths()
    {
        $print_config = $this->config_object->get('debug') ?? true;
        $this->i18n = $this->framework->get_component("i18n");
        $this->current_site = $this->config_object->get('site');
        $this->defaults = $this->config_object->get('defaults');
        $this->paths = $this->config_object->get('paths');
        $this->default_template = $this->defaults['definition']['template'] ?? "main.tpl";
        $this->default_theme = $this->defaults['definition']['theme'] ?? "bootstrap";
        $this->charset = $this->current_site['vars']['charset'] ?? $this->defaults['charset'] ?? "UTF-8";

        $this->current_theme = $this->current_site['vars']['theme'] ?? "";
        if (!$this->current_theme) {
            $this->current_theme = $this->defaults['theme'] ?? "bootstrap";
        }
        $webdir = rtrim($this->paths['webroot'], "/");
        $this->current_site['path'] = $this->paths['sitepath'];
        $this->current_site['vars']['webbase'] = $this->paths['base'];

        if (is_dir($this->paths['themes'] . "/" . $this->current_theme)) {
            $this->themedir = $this->paths['themes'] . "/" . $this->current_theme;
            $this->themepath = $this->paths['webroot'] . "/themes/" . $this->current_theme;
        } 
        // elseif (is_dir($this->paths['base'] . "/local/themes/" . $this->current_theme)) {
        //     $this->themedir = "local/themes/" . $this->current_theme;
        //     $this->themepath = $this->paths['base'] . "/local/themes/" . $this->current_theme;
        //     $this->config_object->set('site.themepath', $this->themepath);
        // } 
        else {
            $this->themedir = $this->paths['webroot'] . "/local/themes/" . $this->current_theme;
            $this->themepath = $this->paths['base'] . "/local/themes/" . $this->current_theme;
        }
        $themefile = $this->themedir . "/theme.yaml";
        $this->config_object->set('site.themepath', $this->themepath);
        $this->config_object->set('site.themefile', $themefile);
        //print $themefile . "<br/>";exit;
        $this->config_object->mergeYaml('site.theme', "$themefile");
        $theme_config = $this->config_object->get('site.theme');
        if (!$theme_config) {
            //print "Theme configuration file not found at $themefile. Using default theme configuration.<br/>";
            $theme_config = [];
        }
        foreach ($theme_config['sections'] ?? [] as $section => $details) {
            if (isset($details['file'])) {
                $fullpath = $this->themedir . "/" . $details['file'];
                $theme_config['sections'][$section]['fullpath'] = $fullpath;
                if(is_file($fullpath)) {
                    $theme_config['sections'][$section]['contents'] = file_get_contents($fullpath);
                } else {
                    $theme_config['sections'][$section]['contents'] = "";
                }
            }
        }

        $masterfile = $theme_config['vars']['master-template'] ?? 'master.tpl';
        $masterpath = $this->themedir . "/$masterfile";
        $this->master = file_get_contents($masterpath);

        $this->config_object->set('site.theme', $theme_config);


    }

    function assign_template_vars()
    {
        $defaults = $this->config_object->get('defaults');
        $left_delim = $this->template_engine->getLeftDelim();
        $right_delim = $this->template_engine->getRightDelim();
        $debug_obj = $this->framework->get_component('debug');
        $palette_definition = $this->current_site['palette'] ?? [];
        $palette_plugin = new \Opensitez\Simplicity\Palette( $this->config_object);
        $palette_vars = $this->current_site['definition']['style'] ?? [];
        $palette = $palette_plugin->get_palette($this->app, $palette_definition, $palette_vars);

        $config = $this->config_object->get('site');

        $this->init_paths();

    
        $menumaker = new Menu($this->config_object);
        $menumaker->set_handler($this->framework);
        // print_r($current_site);exit;

        $menuopts = [
            "linkclass" => "nav-link",
            "menuclass" => "nav-item",
        ];
        $this->template_engine->assign("webroot", $this->paths['webroot']);

        if ($this->current_site['vars']['skipmenu'] ?? []) {
            $menuopts['skip'] = $this->current_site['vars']['skipmenu'] ?? false;
        }
        $menus= $this->current_site['definition']['navigation'] ?? [];

        $navigation = $menumaker->make_menu($menus ?? [], $menuopts);
        $this->template_engine->assign("themepath", $this->current_site['themepath']);
        $this->template_engine->assign("sitepath", $this->paths['sitepath'], true);
        $this->template_engine->assign("navigationmenu", $navigation);

        $this->template_engine->assign("webroot", $this->paths['webroot'], true);
        $this->template_engine->assign("host", $this->current_site['host'], true);
        $this->theme = $this->current_site['theme'] ?? $this->defaults['theme'] ?? "$this->default_theme";
        $this->pagestyle = '';

        foreach ($this->current_site["style"] ?? [] as $key => $value) {
            $this->pagestyle .= "      $key {" . $value . ";}\n";
        }
        $this->pagestyle = "<!-- YOW -->$palette\n<style>\n$this->pagestyle\n</style>\n\n";

        $this->config_object->set("paths.themepath", $this->themepath);
        //print_r($this->config_object->get("paths"));exit;
        foreach ($this->current_site['theme']['js'] ?? [] as $script) {
            $script = $this->replace_paths($script);
            $this->pagestyle .= "<script src='$script'></script>\n";
        }
        foreach ($this->current_site['definition']['js'] ?? [] as $script) {
            $script = $this->replace_paths($script);
            $this->pagestyle .= "<script src='$script'></script>\n";
        }

        foreach ($this->current_site['theme']["css"] ?? [] as $sheet) {
            //print $sheet . "<br/>\n";
            $sheet = $this->replace_paths($sheet);
            $this->pagestyle .= "<link href='$sheet' type='text/css' rel='stylesheet'>\n";
        }
        
        //print_r($this->current_site['definition']['css'] ?? []);
        //print "<hr/>CSS files in definition:<br/>";
        //make sure CSS Works in any order (wedp bootstrap icons)
        foreach ($this->current_site['definition']['css'] ?? [] as $sheet) {
            //print $sheet . "<br/>\n";
            $sheet = $this->replace_paths($sheet);
            $this->pagestyle .= "<link href='$sheet' type='text/css' rel='stylesheet'>\n";
        }
        //exit;

        $template_arrays = [
             "colors" => $this->defaults["colors"] ?? [],
                         "vars" => $this->current_site['vars'] ?? [], "var2s" => $this->current_site['definition']['vars'] ?? [],
        ];

        $this->template_engine->assign("pagestyle", $this->pagestyle);
        $this->template_engine->assign("title", $this->current_site['definition']['vars']['title']??"");
        foreach ($template_arrays as $array_name => $tmp_array) {
            foreach ($tmp_array as $idx => $value) {
                $this->template_engine->assign($idx, $this->replace_paths($value));
                //print "<hr/><h3 tyle=\"color:red;background-color:yellow\">Assigning $array_name.$idx = $value</h3><hr/>\n";
            }
        }
        $this->template_engine->assign("content", $this->replace_paths($this->current_site['vars']['content'] ?? ""));

    }
    function on_render_templates($app)
    {
        $theme = $this->config_object->get('site.theme');
        $sections = [];
        foreach ( $theme['sections'] ?? [] as $section => $details) {
            $sections[$section] = $this->template_engine->render($details['contents'] ?? '',false) ;
            $this->template_engine->assign($section, $sections[$section]);
            //print "<!-- $section -->\n";
            //print $sections[$section] . "<br/>\n";
            //print $section . " - " . $this->template_engine->render($details['contents'] ?? '') . "<br/>\n";
        }
        //$this->template_engine->assign("content","");
        
        $rendered = $this->template_engine->render($this->master,false);
        print "$rendered";

    }
    function show_debug()
    {
        $this->init_paths();
        
        $debug_obj = new \Opensitez\Simplicity\SimpleDebug();

        print "<h3>Current Site</h3>";
        $current_site =$this->config_object->get('site');

        $debug = [
            'paths' => $this->paths,
            'theme' => "$this->current_theme - $this->themedir",
            'themepath' => $this->themepath,
            'template' => $this->default_template,
            'charset' => $this->charset,
        ];

        print $debug_obj->printArray($debug,3);
        print $debug_obj->printArray($current_site,3);
    }
    function on_render_page($app) {
        $this->init_paths();
        //$this->show_debug();exit;
        $engine = $this->config_object->get('site.theme.engine') ?? 'simpletemplate';
        $this->app = $app;
        $template_engine = $this->framework->get_registered_type('templateengine', strtolower($engine));
        
        if ($template_engine) {
            $this->template_engine = $template_engine;
        } else {
            print "Template engine '$engine' not found, falling back to default.<br/>";
            // Fallback to default SimpleTemplate if available
            $default_engine = $this->framework->get_registered_type('templateengine', 'simpletemplate');
            if ($default_engine) {
                $this->template_engine = $default_engine;
            } else {
                throw new \Exception("Template engine '$engine' not found and no default available.");
            }
        }
        
        $this->template_engine->set_handler($this->framework);
        $this->template_engine->engine_init();
        $this->assign_template_vars();
        $rendered = $this->on_render_templates($app);

        //$this->render($app);
        exit;
    }
}
