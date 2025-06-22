<?php

namespace Opensitez\Simplicity\Plugins;

class Checklist extends FormField
{
    protected $rowsize = 5;
    function render()
    {
        $value = $this->value;
        $retval = "";
        $retval .= "<td " . $this->colspan . "><table><tr>";
        $retval .= "<td>" . $this->label . "</td><td>";

        $chkcount = 0;
        $retval .= "<table class='checklist " . $this->name . ".checklist'><tr>";
        if (isset($this->field_def['values']) && $this->field_def["values"]) {
            foreach ($this->field_def["values"] as $chkKey => $chkValue) {
                $chkcount++;
                $retval .= "<td>";
                $val =
                    $retval .= "<input class='' type=checkbox name=\""  . $this->name . "[]\" value=\"$chkKey\" />" . $this->get_i18n_value($chkValue, $this->lang) . " \n";
                $retval .= "</td>";
                if (!($chkcount % $this->rowsize))
                    $retval .= "</tr><tr>";
            }
        }
        $retval .= "</tr></table>";
        $retval .= "</td></tr>";
        $retval .= "</tr></table></td>";
        return $retval;
    }
}
