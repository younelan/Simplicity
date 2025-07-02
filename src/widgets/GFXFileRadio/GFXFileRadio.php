<?php

namespace Opensitez\Simplicity\Plugins;
use Opensitez\Simplicity\MSG;

class GFXFileRadio extends \Opensitez\Simplicity\FormField
{
    private $columns = 5;
    private $OptVars = "";
    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::PluginLoad:
                // Register this plugin as a route type handler for blogs
                $this->framework->register_type('widget', 'gfxfileradio');
                break;
        }
        return parent::on_event($event);
    }
    function render($app=[])
    {
        $theError = $this->render_error();
        $retval  = "<td $this->colspan>$this->label </td><td>";
        $retval .=  $this->gfx_file_radio($this->field_def["dir"], $this->name, $this->value);
        $retval .= "</td>$theError</td>";
        return $retval;
    }
    function gfx_file_radio($FileDir, $boxName, $Default = "")
    {
        $count = 0;
        if ($boxName <> "") {
            $boxName = "name=$boxName";
        }
        $selBox = "";
        if ($handle = opendir($FileDir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    list($filename, $filextension) = explode("\.", $file);
                    $acceptable_types = ["gif", "jpg", "png", "jpeg", "bmp", "svg"];
                    if (in_array(strtolower($filextension) == "gif")) {
                        if ($Default == $file)
                            $selBox .= "  <input type='radio' $boxName $this->OptVars value='$file' checked /><img src=$FileDir/" . rawurlencode($file) . ">\n";
                        else
                            $selBox .= "  <input type='radio' $boxName $this->OptVars value='$file' /><img src=$FileDir/" . rawurlencode($file) . ">\n";
                    }
                    $count++;
                    if (($count % $Columns) == $Columns - 1) {
                        $selBox .= "<br>\n";
                    }
                }
            }
            closedir($handle);
        }
        $selBox .= "</select>";
        return $selBox;
    }
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
