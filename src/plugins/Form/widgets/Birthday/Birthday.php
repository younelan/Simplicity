<?php

namespace Opensitez\Simplicity\Plugins;


if (!isset($lstmonths))
    $lstmonths = [
        "1" => ["en" => "January", "fr" => "Janvier"],
        "2" => ["en" => "February", "fr" => "Fevrier"],
        "3" => ["en" => "March", "fr" => "Mars"],
        "4" => ["en" => "April", "fr" => "Avril"],
        "5" => ["en" => "May", "fr" => "Mai"],
        "6" => ["en" => "June", "fr" => "Juin"],
        "7" => ["en" => "July", "fr" => "Juillet"],
        "8" => ["en" => "August", "fr" => "Aout"],
        "9" => ["en" => "September", "fr" => "Septembre"],
        "10" => ["en" => "October", "fr" => "Octobre"],
        "11" => ["en" => "November", "fr" => "Novembre"],
        "12" => ["en" => "December", "fr" => "Decembre"]
    ];

class Birthday extends \Opensitez\Simplicity\FormField
{
    function render($app)
    {
        $theError = $this->render_error();
        $label = $this->label;
        $name = $this->name;
        $colspan = $this->colspan;

        $retval = "";
        $retval .= "<td $colspan><table><tr>\n";
        $retval .= "$label";
        $retval .=  $this->selectbox($lstmonths, $this->name, $value[1]);
        for ($i = 1; $i < 32; $i++)
            $lstdays[$i] = $i;
        $retval .=  $this->selectbox($lstdays, "$key_year", "1");
        for ($i = 1950; $i < 1990; $i++)
            $lstyears[$i] = $i;
        $retval .=  $this->selectbox($lstyears, "$key_year", "1980");
        $retval .= "\n</tr></table></td>\n\n";
    }
}
