<?php

namespace Opensitez\Simplicity\Plugins;

use Opensitez\Simplicity\MSG;

class TextFormatBlock extends \Opensitez\Simplicity\Plugin
{
    public $name = "TextFormatBlock";
    public $description = "Handles basic text formatting (html, text, nl2br)";
    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::PluginLoad:
                // Register this plugin as a route type handler for redirects
                //$this->framework->register_type('routetype', 'text');
                $this->framework->register_type('blocktype', 'text');
                $this->framework->register_type('blocktype', 'html');
                $this->framework->register_type('blocktype', 'nl2br');
                break;
        }
        return parent::on_event($event);
    }

    function render($block_config, $options = [])
    {
        if (!$block_config) {
            $block_config = $this->options;
        }
        $content_type = $block_config['content-type'] ?? $options['content-type'] ??  'html';
        $text = $block_config['content'] ?? $block_config['text'] ??"";
        
        if (is_array($text)) {
            $text = implode("\n", $text);
        }
        
        switch ($content_type) {
            case "nl2br":
                return nl2br($text);
            case "text":
                return nl2br(htmlentities($text));
            case "html":
            default:
                return $text;
        }
    }
}