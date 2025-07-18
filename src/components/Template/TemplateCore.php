<?php

namespace Opensitez\Simplicity\Components;

use Opensitez\Simplicity\MSG;

class TemplateEngine extends \Opensitez\Simplicity\Component
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
