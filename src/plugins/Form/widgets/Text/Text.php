<?php

namespace Opensitez\Simplicity\Plugins;

class Text extends \Opensitez\Simplicity\FormField
{
    function render($app=[])
    {
        $theError = $this->render_error();
        $label = $this->get_i18n_value($this->label, $this->lang);
        $name = $this->name;
        $colspan = $this->colspan;

        $retval = "";
        $retval .= "<td $colspan><table><tr>\n";
        $retval .=  "<td>$label </td><td><input type=text name=\"$name\" value=\"" . $this->value . "\"> $theError</td>";
        $retval .= "\n</tr></table></td>\n\n";
        return $retval;
    }
}
