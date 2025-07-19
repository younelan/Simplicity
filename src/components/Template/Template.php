<?php

namespace Opensitez\Simplicity\Components;

use Opensitez\Simplicity\MSG;

class Template extends \Opensitez\Simplicity\Component
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

    function register_engine($name, $engine)
    {
        self . $engines[$name];
    }

    function init_paths()
    {
        $this->i18n = $this->framework->get_component("i18n");
        $this->current_site = $this->config_object->get('site');
        $this->defaults = $this->config_object->get('defaults');
        $this->paths = $this->config_object->get('paths');
        $this->default_template = $this->defaults['definition']['template'] ?? "main.tpl";
        $this->default_theme = $this->defaults['definition']['theme'] ?? "bootstrap";
        $this->charset = $this->current_site['vars']['charset'] ?? $this->defaults['charset'] ?? "UTF-8";
    
        // print "<pre>";
        // print_r($this->current_site);

        // print_r($this->paths);
        // $this->template_engine = new SmartyTemplate($this->config_object());
        $this->current_theme = $this->current_site['vars']['theme'] ?? "";
        if (!$this->current_theme)
            $this->current_theme = $this->defaults['theme'] ?? "bootstrap";
        $webdir = rtrim($this->paths['webroot'], "/");
        $this->current_site['path'] = $this->paths['sitepath'];
        $this->current_site['vars']['webbase'] = $this->paths['base'];

        if (is_dir($this->paths['datafolder'] . "/themes/" . $this->current_theme)) {
            $this->themedir = $this->paths['sitepath'] . "/themes/" . $this->current_theme;
        } elseif (is_dir($this->paths['base'] . "/local/themes/" . $this->current_theme)) {
            $this->themedir = "local/themes/" . $this->current_theme;
        } else {
            $this->themedir = "core/themes/" . $this->current_theme;
        }

        $this->options = [
            'current-theme' => $this->current_theme
        ];
        $this->current_site['vars']['webpath']= $webdir;
        $this->template_engine->assign("sitepath", $this->paths['sitepath']);
        $this->current_site['themepath'] = "$webdir/$this->themedir";
    }
    function get_template($app)
    {
        $left_delim = $this->template_engine->getLeftDelim();
        // print $left_delim;exit;
        $right_delim = $this->template_engine->getRightDelim();
        if (is_file($this->paths['base'] . "/$this->themedir/theme.osz")) {
            //$theme_config = file_get_contents($this->paths['base'] . "/$this->themedir/theme.osz");
            $theme_config = \Opensitez\Simplicity\Spyc::YAMLLoad($this->paths['base'] . "/$this->themedir/theme.osz");
            $master = $theme_config['vars']['master'] ?? 'master.tpl';
            $this->master = file_get_contents($this->paths['base'] . "/$this->themedir/$master");
            $this->master = file_get_contents($this->paths['base'] . "/$this->themedir/$master");
            foreach ($theme_config['sections'] as $section => $section_details) {
                $fname = $this->paths['base'] . "/$this->themedir/{$section_details['template']}";
                $current_tag = $left_delim . $section . $right_delim;
                //print " print $left_delim $right_delim $current_tag, $fname<p>\n";
                $this->master = str_replace($current_tag, @file_get_contents($fname), $this->master);
            }
        } else {

            $this->current_template = $this->current_site['vars']['template'] ?? "";
            if (!$this->current_template)
                $this->current_template = $this->default_template;

            $local_theme_file =  $this->paths['themes'] . "/$this->current_theme/$this->current_template";
            $core_theme_file = $this->paths['base'] . "/core/themes/$this->current_theme/$this->current_template";
            if (@file_exists($local_theme_file)) {
                $this->template = $local_theme_file;
                $this->master = file_get_contents($this->template);
            } elseif (@file_exists($core_theme_file)) {
                $this->template = "$core_theme_file";
                $this->master = file_get_contents($this->template);
            } else {
                echo "default, not found";
                $this->template = $this->paths['core'] . "/themes/$this->default_theme/$this->default_template";
                $this->master = file_get_contents($this->template);
            }

            $theme_config = ['sections' => ['content' => 'main.tpl'], 'vars' => ['default-section' => 'content']];
        }
        return $this->master;
    }
    function on_render_template($app)
    {
        $left_delim = $this->template_engine->getLeftDelim();
        $right_delim = $this->template_engine->getRightDelim();

        $this->app = $app;
        $palette = $this->get_palette($app);
        $config = $this->config_object->get('site');

        $this->init_paths();
        $this->get_template($app);
        $menumaker = new Menu($this->config_object);
        $menumaker->set_framework($this->framework);
        // print_r($current_site);exit;

        $menuopts = [
            "linkclass" => "nav-link",
            "menuclass" => "nav-item",
        ];

        if ($this->current_site['vars']['skipmenu'] ?? []) {
            $menuopts['skip'] = $this->current_site['vars']['skipmenu'] ?? false;
        }
        $navigation = $menumaker->make_menu($this->current_site["data"]["navigation"] ?? [], $menuopts);

        $this->template_engine->assign("themepath", $this->current_site['themepath'], true);
        $this->template_engine->assign("webroot", $this->paths['webroot'], true);
        $this->template_engine->assign("host", $this->current_site['domain'], true);
        $this->theme = $this->current_site['theme'] ?? $this->defaults['theme'] ?? "$this->default_theme";
        $this->pagestyle = '';

        foreach ($this->current_site["style"] ?? [] as $key => $value) {
            $this->pagestyle .= "      $key {" . $value . ";}\n";
        }
        $this->pagestyle = "$palette\n<style>\n$this->pagestyle\n</style>\n\n";

        foreach ($this->current_site['js'] ?? [] as $script) {
            $this->pagestyle .= "<script src='$script'></script>\n";
        }

        foreach ($this->current_site["css"] ?? [] as $sheet) {
            $this->pagestyle .= "<link href='$sheet' type='text/css' rel='stylesheet'>\n";
        }

        $template_arrays = [
            "blocks" => $this->current_site["blocks"] ?? [], "colors" => $this->defaults["colors"] ?? [], "vars" => $this->current_site['vars'] ?? [],
            //"config"=>$config,
        ];
        foreach ($template_arrays as $array_name => $tmp_array) {
            foreach ($tmp_array as $idx => $value) {
                $this->template_engine->assign($idx, $value, true);
            }
        }
        
        foreach ($this->current_site["vars"] as $key => $value) {

            if (is_string($value)) {
                $tplvar = $left_delim . $key . $right_delim;
                //print "$tplvar\n";
                $this->pagestyle = str_replace($tplvar, $value, $this->pagestyle);
                $this->current_site['vars']['content'] = str_replace($tplvar, $value, $this->current_site['vars']['content'] ?? "");
            }
        }
        $navigation = str_replace($left_delim . "sitepath" . $right_delim, $this->paths['sitepath'], $navigation);

        $this->template_engine->assign("navigationmenu", $navigation);
        $this->template_engine->assign("charset", $this->charset);

        $this->pagestyle = str_replace($left_delim . 'themepath' . $right_delim, $this->current_site["themepath"], $this->pagestyle);
        $this->template_engine->assign("pagestyle", $this->pagestyle, true);

        foreach ($this->current_site["vars"] as $key => $value) {
            $this->template_engine->assign(trim($key), $this->i18n->get_i18n_value($value), true);
        }
        //$print_config=true;
        $print_config = false;
        if (isset($print_config) && $print_config) {
            if (!isset($this->current_site['debug']))
                $config["debug"] = [];
            $this->template_engine->assign('content', "<pre><font color=$left_delim$pagefg$right_delim>" . var_export($this->current_site, true));
        }
        //print $this->template;exit;
        //print $this->master;

    }
    function on_render_page($app)
    {
        //$engine = $this->framework->get_component("smartytemplate");
        $engine = "simplicity";
        switch ($engine) {
            case 'smarty':
                $this->template_engine = new \Opensitez\Simplicity\Components\SmartyTemplate($this->config_object);
                break;
            case 'twig':
                $this->template_engine = new \Opensitez\Simplicity\Components\TwigTemplate($this->config_object);
                break;
            case 'simplicity':
            default:
                $this->template_engine = new \Opensitez\Simplicity\Components\SimpleTemplate($this->config_object);
        }
        $this->template_engine->set_framework($this->framework);
        $this->template_engine->engine_init();
        $this->on_render_template($app);

        $rendered = $this->template_engine->render($this->master);

        return $rendered;
    }
}
