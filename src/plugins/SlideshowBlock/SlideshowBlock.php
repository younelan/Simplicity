<?php

namespace Opensitez\Simplicity\Plugins;

use Opensitez\Simplicity\MSG;

class SlideshowBlock extends \Opensitez\Simplicity\Plugin
{
    public $name = "SlideshowBlock";
    public $description = "Renders slideshow content";

    function on_event($event)
    {
        if ($event['type'] === MSG::PluginLoad) {
            $this->plugins->register_type('blocktype', 'slideshow');
        }
        parent::on_event($event);
    }

    function render($text, $options = [])
    {
        $slidestyle = addslashes($options['style'] ?? "");
        $carouselclass = $options['carousel-class'] ?? [];
        $datainterval = $options['interval'] ?? "";
        
        if ($datainterval) {
            $datainterval = " data-interval=\"$datainterval\" ";
        } else {
            $datainterval = "";
        }
        
        if ($slidestyle) {
            $slidestyle = " style=\"$slidestyle\" ";
        } else {
            $slidestyle = "";
        }
        
        if (!is_array($carouselclass)) {
            $carouselclass = [$carouselclass];
        }
        
        if ($carouselclass) {
            $carouselclass = implode(" ", $carouselclass);
        } else {
            $carouselclass = "";
        }

        $parsed_content = '<div ' . $slidestyle . $datainterval . '" class="carousel slide ' . $carouselclass . '" data-bs-ride="carousel">';
        $parsed_content .= '<div class="carousel-inner">';
        
        $start = $options['start'] ?? 0;
        $idx = 0;
        
        if (!is_array($text)) {
            $text = explode("\n", $text);
        }
        
        foreach ($text as $line) {
            if ($line) {
                $active = ($start == $idx) ? "active" : "";
                $parsed_content .= "<div class='carousel-item $active'>$line\n</div>";
            }
            $idx += 1;
        }
        
        $parsed_content .= "\n</div>\n</div>";
        return $parsed_content;
    }
}