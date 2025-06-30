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
                //$this->plugins->register_type('routetype', 'text');
                $this->plugins->register_type('blocktype', 'text');
                $this->plugins->register_type('blocktype', 'html');
                $this->plugins->register_type('blocktype', 'nl2br');
                break;
        }
        return parent::on_event($event);
    }

    function render($block_config, $options = [])
    {
        //print_r($block_config);exit;
        $content_type = $block_config['content-type'] ?? $options['content-type'] ??  'html';
        $text = $block_config['content'] ?? $block_config;
        
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