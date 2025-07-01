<?php

namespace Opensitez\Simplicity\Plugins;

use Opensitez\Simplicity\MSG;
use \Parsedown;

class MarkdownBlock extends \Opensitez\Simplicity\Plugin
{
    public $name = "MarkdownBlock";
    public $description = "Renders markdown content";

    function on_event($event)
    {
        if ($event['type'] === MSG::PluginLoad) {
            $this->framework->register_type('blocktype', 'markdown');
        }
        parent::on_event($event);
    }

    function render($block_config)
    {
        if(!$block_config) {
            $block_config = $this->options;
        }
        $text = $block_config['content'] ?? $block_config;

        if (is_array($text)) {
            $text = implode("\n", $text);
        }
        
        $Parsedown = new Parsedown();
        return $Parsedown->text($text);
    }
}