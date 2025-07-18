<?php

namespace Opensitez\Simplicity;
use Opensitez\Simplicity\MSG;

class Palette extends \Opensitez\Simplicity\Component
{
    var $current_site = [];
    var $basedir = false;
    var $paths=".";
    var $defaults = false;
    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::onComponentLoad:
                // Register this component as a palette provider
                $this->framework->register_type('paletteprovider', 'palette');
                break;
            case MSG::onSetPalette:
                $this->setPalette();
                break;
        }
        return parent::on_event($event);
    }   
    function get_palette($app, $palette_definition = [],$vars = []) {
        $current_site = $this->config_object->get('site');   

        $basedir = $palette_definition['folder'];
        // $this->debug(print_r($palette_definition, true)); //exit;
        $css = $palette_definition['css'] ?? [];
        $styles = $palette_definition['style'] ?? [];
        $content = "";
        if ($css) {
            $content .= $this->include_files("$basedir", $css)  ;
        }
        // $this->debug(print_r($vars, true));
        if ($styles) {
            $styles_content = $this->make_style_rules($styles);
        } else {
            $styles_content = "";
        }

        if($vars) {
            $styles_content = $this->substitute_vars($styles_content, $vars);
        }
        $content .= "<!--fun-->" . $styles_content;
        // $this->debug($content);
        return $content;

    }
    function include_files($basedir, $files = [])
    {
        $retval = "";
        $systempaths = $this->config_object->get('system.paths');

        if ($files) {
            if (!is_array($files)) {
                $files = [$files];
            }
            foreach ($files as $file) {
                $fname = "$basedir/$file";
                if (file_exists($fname)) {
                    $this->debug("Loading palette file: $fname<br/>\n");
                    $retval .= "<style>\n" . file_get_contents($fname) . "</style>\n";
                } elseif (file_exists($systempaths['palettes'] . "/$file")) {
                    $this->debug("Loading palette file: $file<br/>\n");
                    $fname = $systempaths['palettes'] . "/$file";
                    $retval .= "<style>\n" . file_get_contents($fname) . "</style>\n";
                } 
                // elseif (file_exists($this->basedir . "/$file")) {
                //     $fname = $this->basedir . "/$file";
                //     $retval .= "<style>\n" . file_get_contents($fname) . "</style>\n";
                // } 
                else {
                    $this->debug("File not found: $fname<br/>\n");
                    $this->debug("File not found: " . $systempaths['palettes'] . "/$file<br/>\n");
                }
            }
            //exit;
        }
        return $retval;
    }
    function make_style_rules($styles = [])
    {
        $retval = "";
        if ($styles) {
            foreach ($styles ?? [] as $rule_name => $style) {
                $rule = "";
                if(!is_array($style)) {
                    print "Style for $rule_name is not an array: " . print_r($style, true) . "<br/>\n";
                    continue;
                }
                foreach ($style ?? [] as $attr => $arr_value) {
                    $rule .= "    $attr: $arr_value;\n";
                }
                $retval .= "$rule_name {\n$rule}\n";
            }
        }
        return "<style>\n" . $retval . "\n</style>\n\n";
    }

    function setPalette()
    {
        $site = $this->config_object->get("site", []);
        $site_palettes = $this->config_object->get(
            "site.definition.palettes",
            []
        );
        $default_palettes = $this->config_object->get("palettes", []);
        $system_palettes = $this->config_object->get("system.palettes", []);
        $default_palette_name = $this->config_object->get(
            "defaults.palette",
            "dark-red"
        );
        $current_palette_name = $this->config_object->get(
            "site.definition.vars.palette",
            $default_palette_name
        );

        if (isset($site_palettes[$current_palette_name])) {
            $current_palette = $site_palettes[$current_palette_name];
            $current_palette["name"] = $current_palette_name;
            $current_palette["type"] = "site";
            $current_palette["folder"] =
                $this->config_object->get("paths.sites") .
                "/" .
                $site["folder"] .
                "/css";
        } elseif (isset($default_palettes[$current_palette_name])) {
            $current_palette = $default_palettes[$current_palette_name];
            $current_palette["name"] = $current_palette_name;
            $current_palette["type"] = $current_palette["type"] ?? "default";
            $current_palette["folder"] =
                $this->config_object->get("paths.base") .
                "/local/config/css/" .
                $current_palette_name;
        } elseif (isset($system_palettes[$current_palette_name])) {
            $current_palette = $system_palettes[$current_palette_name] ?? [];
            $current_palette["name"] = $current_palette_name;
            $current_palette["type"] = "system";

            $current_palette["folder"] =
                $this->config_object->get("system.paths.palettes") ?? "";
        } else {
            $current_palette = $default_palettes["desktop"] ?? [];
            $current_palette["name"] = "desktop";
            $current_palette["type"] = "default";
            $current_palette["folder"] =
                $this->config_object->get("system.paths.palettes") ?? "";
        }
        $this->config_object->set("site.palette", $current_palette);
    }

    function render($app)
    {
        $this->current_site = $this->config_object->get('site');
        $defaults = $this->config_object->get('defaults');
        $this->paths = $this->config_object->get('paths');
        $this->basedir = $this->paths['core-templates'];
        $palette = $_SESSION['user_data']['palette'] ?? $this->current_site['vars']['palette'] ?? $this->defaults['vars']['palette'] ?? "desktop";
        if (isset($this->current_site['palettes'][$palette])) {
            $palette_details = $this->current_site['palettes'][$palette];
        } elseif (isset($this->defaults['palettes'][$palette])) {
            $palette_details = $this->current_site['palettes'][$palette];
        } else {
            $palette = $this->config['vars']['palette'] ?? "desktop";
            $palette_details = $this->defaults['palettes'][$palette] ?? [];
        }
        $styles = "";
        $style_files = $palette_details['css'] ?? [];
        if ($style_files) {
            if (!is_array($style_files)) {
                $style_files = [$style_files];
            }
            foreach ($style_files as $style_file) {
                $fname = "$this->basedir/css/$style_file";
                // $this->debug("$fname<br/>");
                $styles .=  file_get_contents($fname) . "\n";
            }
        }
        // $this->debug(print_r($style_files, true));
        // exit;
        $custom_css = "\n/*** Custom palette **/\n\n";
        $palette_styles = $palette_details["style"] ?? [];
        foreach ($palette_styles as $rule_name => $style) {
            $rule = "";
            foreach ($style as $attr => $arr_value) {
                $rule .= "    $attr: $arr_value;\n";
            }
            $custom_css .= "$rule_name {\n$rule}\n";
        }
        $retval = "<style>\n" . $styles . $custom_css . "\n</style>\n\n";
        return $retval;
    }
}
