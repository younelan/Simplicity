<?php

namespace Opensitez\Simplicity\Plugins;

use Opensitez\Simplicity\MSG;

class IncludeBlock extends \Opensitez\Simplicity\Plugin
{
    public $name = "IncludeBlock";
    public $description = "Includes content from external files";

    function on_event($event)
    {
        if ($event['type'] === MSG::PluginLoad) {
            $this->plugins->register_type('blocktype', 'include');
        }
        parent::on_event($event);
    }

    function render($text, $options = [])
    {
        $i18n = $this->plugins->get_plugin('i18n');
        $paths = $this->config_object->getPaths();
        
        $incfile = $text;
        if ($i18n) {
            $incfile = $i18n->get_i18n_value($incfile);
        }
        
        $found = false;
        $file_path = $paths["datafolder"] . "/" . $incfile;
        
        if ($i18n) {
            foreach ($i18n->accepted_langs() as $lang => $lang_details) {
                if ((ctype_alpha($lang) && strlen($lang) == 2) && is_file($file_path . ".$lang")) {
                    $fcontents = @file_get_contents($file_path . ".$lang");
                    $found = true;
                    break;
                }
            }
        }
        
        if (!$found) {
            $fcontents = @file_get_contents($paths["datafolder"] . "/" . $incfile);
        }
        
        // Process the included content through TextBlock if available
        $textblock = $this->plugins->get_plugin('textblock');
        if ($textblock) {
            return $textblock->render_insert_text($fcontents, $options);
        }
        
        return $fcontents;
    }
}