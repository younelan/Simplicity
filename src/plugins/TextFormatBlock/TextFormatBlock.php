<?php

namespace Opensitez\Simplicity\Plugins;

use Opensitez\Simplicity\MSG;

class TextFormatBlock extends \Opensitez\Simplicity\Plugin
{
    public $name = "TextFormatBlock";
    public $description = "Handles basic text formatting (html, text, nl2br)";

    function on_event($event)
    {
        if ($event['type'] === MSG::PluginLoad) {
            $this->plugins->register_type('blocktype', 'html');
            $this->plugins->register_type('blocktype', 'text');
            $this->plugins->register_type('blocktype', 'nl2br');
        }
        parent::on_event($event);
    }

    function render($text, $options = [])
    {
        $content_type = $options['content-type'] ?? 'html';
        
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