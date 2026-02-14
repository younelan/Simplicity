<?php

namespace Opensitez\Simplicity\Widgets;
use Opensitez\Simplicity\MSG;

class Currency extends \Opensitez\Simplicity\FormField
{
    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::onComponentLoad:
                $this->framework->register_type('widget', 'currency');
                break;
        }
        return parent::on_event($event);
    }
    function render($app)
    {
        $retval = "";
        $retval .= "<td $colspan><table><tr>\n";
        $retval .=  "<td>$label </td><td><input type=text name=\"$key\" value=\"" . $FormValues[$key] . "\"> $theError</td>";
        $retval .= "\n</tr></table></td>\n\n";
        return $retval;
    }
}
