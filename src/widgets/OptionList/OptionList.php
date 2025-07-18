<?php

namespace Opensitez\Simplicity\Components;

class OptionList extends \Opensitez\Simplicity\FormField
{
    var $linecount = 4;
    function render($app=[])
    {
        $theError = $this->render_error();
        $label = $this->label;
        $name = $this->name;
        $colspan = $this->colspan;

        $retval = "";
        $retval .= "<td $colspan><table><tr>";
        $optval = "";
        if (isset($this->field_def["size"]))
            $optval .= " size='" . $this->field_def["size"] . "' ";
        if (!isset($this->field_def["default"]))
            $this->field_def['default'] = "";
        $retval .= "<td>$label </td><td>";
        $retval .=  $this->optionlist($this->listvals, $this->name, $this->value, $optval);
        $retval .=  " $theError</td>";
        $retval .= "</tr></table></td>";
        return $retval;
    }
    function optionlist($Values, $SelName, $Default = "", $OptVars = "")
    {
        $linecount = $this->linecount;
        if ($SelName <> "") $SelName = "name=$SelName";
        $selBox = "<table><tr>";
        if ($Values) {
            $tmpCount = 0;
            foreach ($Values as $key => $value) {
                $tmpCount++;
                $value = $this->get_i18n_value($value, $this->lang);
                if ($Default == $key)
                    $selBox .= "<td><label> <input type=radio checked name='$SelName' value='$key' />
            $value</label></td>\n";
                else
                    $selBox .= "<td><label> <input type=radio name='$SelName' value='$key' />
            $value</label></td>\n";
                if (!($tmpCount % $linecount)) $selBox .= "</tr><tr>\n";
            }
        }
        $selBox .= "</select></tr></table>";
        return $selBox;
    }
}
