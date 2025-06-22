<?php

namespace Opensitez\Simplicity\Plugins;

use Opensitez\Simplicity\MSG;

class TextBlock extends Block
{
    function render_insert_text($text, $options = [])
    {
        $default = [
            'encoding' => 'utf-8',
            'content-type' => 'html'
        ];

        $content_type = $options['content-type'] ?? $default['content-type'];
        
        // Try to get a registered block type plugin
        $block_plugin = $this->plugins->get_registered_type('blocktype', $content_type);
        if ($block_plugin && method_exists($block_plugin, 'render')) {
            return $block_plugin->render($text, $options);
        }

        // Fallback to default text handling
        if (is_array($text)) {
            $text = implode("\n", $text);
        }
        
        return $text;
    }
}
