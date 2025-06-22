<?php

namespace Opensitez\Simplicity\Plugins;

class Hidden extends FormField
{
    function render()
    {
        $name = $this->name;

        $retval = "";
        $retval .=  "<input type=hidden name=\"$this->name\" value=" . $this->value . ">";
        return $retval;
    }
}
