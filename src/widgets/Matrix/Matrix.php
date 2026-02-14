<?php

namespace Opensitez\Simplicity\Widgets;

class Matrix extends \Opensitez\Simplicity\FormField
{
    function render($app=[])
    {
        $theError = $this->render_error();
        $label = $this->label;
        $name = $this->name;
        $colspan = $this->colspan;

        $retval = "";
        $retval .= "<td $colspan><table><tr>\n";
        $optval = "";
        if (isset($this->field_def["size"])) $optval .= " size='" . $this->field_def["size"] . "' ";
        if (!isset($this->field_def["default"])) $this->field_def['default'] = "";
        $retval .= "<td>$label </td><td>";
        $retval .=  $this->matrix($listvals, $this->field_def['values'], $this->name, $this->value, $optval);
        $retval .=  " $theError</td>";
        $retval .= "\n</tr></table></td>\n\n";
        return $retval;
    }
    function matrix($Values, $MatrixValues, $SelName, $Default = "", $OptVars = "")
    {
        if ($SelName <> "")
            $SelName = "name=$SelName";
        if ($Values) {
            $selBox .= "\n\n<table><tr><td>$key</td>";
            foreach ($MatrixValues as $matrixkey => $matrixvalue) {
                if (is_array[$matrixvalue]) {
                    $selBox .= "<td>" . $matrixvalue[$this->lang] . "&nbsp;&nbsp;</td>\n";
                } else {
                    $selBox .= "<td>$matrixvalue&nbsp;&nbsp;</td>\n";
                }
            }
            $selBox .= "</tr>";
            foreach ($Values as $key => $value) {
                $selBox .= "\n<tr><td>$value</td>\n";
                foreach ($MatrixValues as $matrixkey => $matrixvalue) {
                    if (1 == 1)
                        $selBox .= "  <td>\n    <input type=radio checked name='$SelName_$matrixvalue' value='$matrixkey' /></td>\n";
                    else
                        $selBox .= "  <td>\n     <input type=radio name='$SelName' value='$key' /></td>\n";
                }
            }
            $selBox .= "</tr></table>";
        }
        return $selBox;
    }
}
