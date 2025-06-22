<?php

namespace Opensitez\Simplicity\Plugins;

class Select extends FormField
{
    function render()
    {
        $theError = $this->render_error();
        $label = $this->get_i18n_value($this->label, $this->lang);
        $name = $this->name;
        $colspan = $this->colspan;
        $listvals = $this->listvals;
        $retval = "";
        $retval .= "<td $colspan><table><tr>\n";
        $optval = "";
        if (isset($this->field_def["size"])) {
            $optval .= " size='" . $this->field_def["size"] . "' ";
        }
        if (!isset($this->field_def["default"])) {
            $this->value = "";
        }
        $retval .= "<td>$label </td><td>";
        $retval .=  $this->selectbox($listvals, $name, $this->value, $optval);
        $retval .=  " $theError</td>";
        $retval .= "\n</tr></table></td>\n\n";
        return $retval;
    }
}
