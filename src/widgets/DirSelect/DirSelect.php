<?php

namespace Opensitez\Simplicity\Plugins;
use Opensitez\Simplicity\MSG;

class DirSelect extends \Opensitez\Simplicity\FormField
{
    function render($app=[])
    {
        $retval = "";
        $theError = $this->render_error();
        $retval .= "<td $this->colspan>$this->label </td><td>";
        $retval .=  $this->dir_select($this->field_def["dir"], $this->name, $this->value);
        $retval .= "</td>$theError</td>";
        return $retval;
    }
    function dir_select($FileDir, $boxName, $Default = "", $Columns = 5, $OptVars = "")
    {
        $count = 0;
        if ($boxName <> "") $boxName = "name=$boxName";
        $selBox = "<select $boxName>";
        if ($handle = opendir($FileDir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != ".." && is_dir("$FileDir/$file")) {
                    list($filename, $filextension) = split("\.", $file);
                    if ($pref_theme == $file)
                        $selBox .= "<option value='$file' selected>$file</option>\n";
                    else
                        $selBox .= "<option value='$file'>$file</option>\n";
                    if ($Default == $file)
                        $selBox .= "<option value='$file' selected>$file</option>\n";
                    else
                        $selBox .= "<option value='$file'>$file</option>\n";
                    $count++;
                    if (($count % $Columns) == $Columns - 1) $selBox .= "<br>\n";
                }
            }
            closedir($handle);
        }
        $selBox .= "</select>";
        return $selBox;
    }
}
