<?php

namespace Opensitez\Simplicity\Plugins;

use Opensitez\Simplicity\MSG;

class TemplateEngine extends \Opensitez\Simplicity\Plugin
{
    function set_file($fname)
    {
        self . $template = $fname;
    }
    function register_engine($name, $engine)
    {
        self . $engines[$name];
    }
    function render()
    {
        return "";
    }
}
