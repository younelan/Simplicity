<?php

namespace Opensitez\Simplicity\Components;
use Opensitez\Simplicity\MSG;

class Hidden extends \Opensitez\Simplicity\FormField
{

    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::onComponentLoad:
                $this->framework->register_type('widget', 'hidden');
                break;
        }
        return parent::on_event($event);
    }
    function render($app=[])
    {
        $name = $this->name;

        $retval = "";
        $retval .=  "<input type=hidden name=\"$this->name\" value=" . $this->value . ">";
        return $retval;
    }
}
