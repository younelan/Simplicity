<?php

namespace Opensitez\Simplicity\Components;

use Opensitez\Simplicity\MSG;

class SlideshowBlock extends \Opensitez\Simplicity\Component
{
    public $name = "SlideshowBlock";
    public $description = "Renders slideshow content";

    function on_event($event)
    {
        if ($event['type'] === MSG::onComponentLoad) {
            $this->framework->register_type('blocktype', 'slideshow');
        }
        parent::on_event($event);
    }

    function render($block_config, $options = [])
    {
        $text = $block_config['content'] ?? $block_config;
        $slidestyle = addslashes($block_config['style'] ?? $options['style'] ?? "");
        $carouselclass = $block_config['carousel-class'] ?? $options['carousel-class'] ?? [];
        $datainterval = $block_config['interval'] ?? $options['interval'] ?? "";
        
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
        
        $start = $block_config['start'] ?? $options['start'] ?? 0;
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