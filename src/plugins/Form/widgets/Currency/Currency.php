<?php

namespace Opensitez\Simplicity\Plugins;

class Currency extends \Opensitez\Simplicity\FormField
{
    function render($app)
    {
        $retval = "";
        $retval .= "<td $colspan><table><tr>\n";
        $retval .=  "<td>$label </td><td><input type=text name=\"$key\" value=\"" . $FormValues[$key] . "\"> $theError</td>";
        $retval .= "\n</tr></table></td>\n\n";
        return $retval;
    }
}
