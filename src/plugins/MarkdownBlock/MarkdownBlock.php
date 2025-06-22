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
            $this->plugins->register_type('blocktype', 'markdown');
        }
        parent::on_event($event);
    }

    function render($text, $options = [])
    {
        $Parsedown = new Parsedown();
        return $Parsedown->text($text);
    }
}