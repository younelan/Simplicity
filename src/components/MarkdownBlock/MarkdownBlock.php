<?php

namespace Opensitez\Simplicity\Components;

use Opensitez\Simplicity\MSG;
use League\CommonMark\CommonMarkConverter;

class MarkdownBlock extends \Opensitez\Simplicity\Component
{
    public $name = "MarkdownBlock";
    public $description = "Renders markdown content";

    function on_event($event)
    {
        if ($event['type'] === MSG::onComponentLoad) {
            $this->framework->register_type('blocktype', 'markdown');
        }
        parent::on_event($event);
    }
    function temp ()
    {


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

        $converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        $retval = $converter->convert($text);

        return $retval;
    }
}