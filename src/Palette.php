<?php

namespace Opensitez\Simplicity;

class Palette extends \Opensitez\Simplicity\Plugin
{
    var $current_site = [];
    var $basedir = false;
    var $paths=".";
    var $defaults = false;
    function render($app)
    {
        $this->current_site = $this->config_object->getCurrentSite();
        $defaults = $this->config_object->getDefaults();
        $this->paths = $this->config_object->getPaths();
        $this->basedir = $this->paths['core-templates'];
        $palette = $_SESSION['user_data']['palette'] ?? $this->current_site['vars']['palette'] ?? $this->defaults['vars']['palette'] ?? "desktop";
        if (isset($this->current_site['palettes'][$palette])) {
            $palette_details = $this->current_site['palettes'][$palette];
        } elseif (isset($this->defaults['palettes'][$palette])) {
            $palette_details = $this->current_site['palettes'][$palette];
        } else {
            $palette = $this->config['vars']['palette'] ?? "desktop";
            $palette_details = $this->defaults['palettes'][$palette];
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
