<?php

namespace Opensitez\Simplicity\Plugins;

class FileSelect extends \Opensitez\Simplicity\FormField
{
    function render($app=[])
    {
        $theError = $this->render_error();

        $retval  = "\n<td $this->colspan><table><tr>";
        $retval .= "<td>$this->label </td><td>";
        $retval .=  $this->file_selectbox($this->field_def["dir"], $this->name, this->value);
        $retval .=  "</td><td>$theError</td>";
        $retval .= "</tr></table></td>\n\n";

        return $retval;
    }
    function file_selectbox($FileDir, $SelName, $Default = "", $OptVars = "")
    {
        if ($SelName <> "") $SelName = "name=$SelName";
        $selBox = "<select $SelName $OptVars>";
        if ($handle = opendir($FileDir)) {

            while (false !== ($file = readdir($handle))) {
                echo $file . "<br>";
                if ($file != "." && $file != "..") {
                    list($filename, $filextension) = split("\.", $file);
                    if (strtolower($filextension) == "gif")
                        if ($Default == $file)
                            $selBox .= "<option value='$file' selected>$filename</option>\n";
                        else
                            $selBox .= "<option value='$file'>$filename</option>\n";
                }
            }
            closedir($handle);
        }
        $selBox .= "</select>";
        return $selBox;
    }
}
