<?php

namespace Opensitez\Simplicity\Plugins;

class TextArea extends FormField
{
    function render()
    {
        $theError = $this->render_error();
        $label = $this->get_i18n_value($this->label, $this->lang);
        $name = $this->name;
        $colspan = $this->colspan;

        $retval = "";
        $retval .= "<td $colspan><table><tr>\n";
        if (isset($this->field_def["cols"]))
            $cols = " cols=" . $this->field_defs['cols'];
        else
            $cols = "";

        if (isset($this->field_defs["rows"]))
            $rows = " rows=" . $this->field_defs['rows'];
        else
            $rows = "";

        $retval .=  "<td>$label </td><td><textarea $cols $rows name=\"$name\">" . $this->value . "</textarea> $theError</td>";
        $retval .= "\n</tr></table></td>\n\n";
    }
}
