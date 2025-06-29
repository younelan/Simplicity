<?php

namespace Opensitez\Simplicity;

class Palette extends \Opensitez\Simplicity\Plugin
{
    var $current_site = [];
    var $basedir = false;
    var $paths=".";
    var $defaults = false;
    function get_palette($app, $palette_definition = [],$vars = []) {
        $current_site = $this->config_object->get('site');   

        $basedir = $palette_definition['folder'];
        //print_r($palette_definition);exit;
        $css = $palette_definition['css'] ?? [];
        $styles = $palette_definition['style'] ?? [];
        $content = "";
        if ($css) {
            $content .= $this->include_files("$basedir", $css)  ;
        }
        //print_r($vars);
        if ($styles) {
            $styles_content = $this->make_style_rules($styles);
        } else {
            $styles_content = "";
        }

        if($vars) {
            $styles_content = $this->substitute_vars($styles_content, $vars);
        }
        $content .= "<!--fun-->" . $styles_content;
        //print $content;
        return $content;

    }
    function include_files($basedir, $files = [])
    {
        $retval = "";
        if ($files) {
            if (!is_array($files)) {
                $files = [$files];
            }
            foreach ($files as $file) {
                $fname = "$basedir/$file";
                if (file_exists($fname)) {
                    $retval .= "<style>\n" . file_get_contents($fname) . "</style>\n";
                } else {
                    echo "File not found: $fname<br/>\n";
                }
            }
        }
        return $retval;
    }
    function make_style_rules($styles = [])
    {
        $retval = "";
        if ($styles) {
            foreach ($styles as $rule_name => $style) {
                $rule = "";
                foreach ($style as $attr => $arr_value) {
                    $rule .= "    $attr: $arr_value;\n";
                }
                $retval .= "$rule_name {\n$rule}\n";
            }
        }
        return "<style>\n" . $retval . "\n</style>\n\n";
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
                //                    print "$fname<br/>";
                $styles .=  file_get_contents($fname) . "\n";
            }
        }
        // print_r($style_files);
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
