<?php

namespace Opensitez\Simplicity\Plugins;

use Opensitez\Simplicity\MSG;



class RainbowTextBlock extends \Opensitez\Simplicity\Plugin
{
    public $name = "RainbowTextBlock";
    public $description = "Renders rainbow colored text";

    function on_event($event)
    {
        if ($event['type'] === MSG::onComponentLoad) {
            $this->framework->register_type('blocktype', 'rainbow-text');
        }
        parent::on_event($event);
    }

    function render($block_config, $options = [])
    {
        $text = $block_config['content'] ?? $block_config;

        $retval = "";
        $encoding = $block_config['encoding'] ?? $options['encoding'] ?? "utf-8";
        $style = $block_config['style'] ?? $options['style'] ?? "font-family:monospace;";
        $colors = $block_config['colors'] ?? $options['colors'] ?? [
            ["bg" => "#000000", "fg" => "#ffffff"],
            ["bg" => "#AA0000", "fg" => "#ffffff"],
            ["bg" => "#FF0000", "fg" => "#ffffff"],
            ["bg" => "#DF7401", "fg" => "#ffffff"],
            ["bg" => "#deb887", "fg" => "0"],
            ["bg" => "#6495ed", "fg" => "#ffffff"],
            ["bg" => "#088A85", "fg" => "#ffffff"],
            ["bg" => "#4B088A", "fg" => "#ffffff"],
            ["bg" => "#006900", "fg" => "#FFFFFF"],
            ["bg" => "#04B404", "fg" => "#FFFFFF"],
            ["bg" => "#77FF77", "fg" => "#0"]
        ];
        
        if (is_array($text)) {
            $text = implode("\n", $text);
        }
        
        $colorcount = count($colors);
        $lines = explode("\n", $text);
        
        $retval .= "<style>\n";
        $retval .= " .rainbowline {font-family: monospace} ";
        
        foreach ($colors as $idx => $color) {
            $bg = $color['bg'] ?? "black";
            $fg = $color['fg'] ?? "white";
            $retval .= "  .color$idx {background-color: $bg; color:$fg; display: inline-block; font-weight: bold; min-width: 15px;text-align:center;font-family: monospace!important}\n";
        }
        
        $retval .= ".rainbowline {$style}\n";
        $retval .= "</style>";

        foreach ($lines as $line) {
            $color = 2;
            $line = trim($line);
            
            while (strstr($line, "(")) {
                if ($color == $colorcount) {
                    $color = 0;
                }
                $textstyle = "<span class='color$color'>";
                $line = $this->str_replace_once("(", $textstyle, $line);
                $line = $this->str_replace_once(")", "</span>", $line);
                $color++;
            }
            
            $retval .= "\n<div class=rainbowline>$line&nbsp;</div>";
        }
        
        $retval = htmlspecialchars_decode(htmlentities($retval, ENT_NOQUOTES, $encoding, FALSE));
        return $retval;
    }
}