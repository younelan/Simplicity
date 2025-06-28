<?php

namespace Opensitez\Simplicity\Plugins;

class Hidden extends \Opensitez\Simplicity\FormField
{
    function render($app=[])
    {
        $name = $this->name;

        $retval = "";
        $retval .=  "<input type=hidden name=\"$this->name\" value=" . $this->value . ">";
        return $retval;
    }
}
