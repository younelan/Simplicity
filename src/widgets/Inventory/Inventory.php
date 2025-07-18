<?php

namespace Opensitez\Simplicity\Components;
use Opensitez\Simplicity\MSG;

class Inventory extends \Opensitez\Simplicity\FormField
{
    protected $rowsize = 3;
    protected $maxlength = 3;
    protected $size = 3;
    protected $invValue = 0;
    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::onComponentLoad:
                $this->framework->register_type('widget', 'inventory');
                break;
        }
        return parent::on_event($event);
    }
    function render($app=[])
    {
        $value = $this->value;

        $retval = "<style></style>";
        $retval .= "<td " . $this->colspan . "><table><tr>";
        $retval .= "<td>" . $this->label . "</td><td>";
        $inv_value = 0;
        $chkcount = 0;
        $retval .= "<table class='inventory " . $this->name . ".inventory'><tr>";
        foreach ($this->field_def["values"] as $chkKey => $chkValue) {
            $chkcount++;
            $retval .= "<td>";
            $val = $this->get_i18n_value($chkValue, $this->lang);
            //print_r( $this->get_i18n_value($chkValue,$this->lang,$debug=true));exit;
            $retval .= "<input class='' type=text size=\"$this->size\" maxlength=\"$this->maxlength\" name=\""  . $this->name . "[]\" value=\"$inv_value\" />" . $val . " \n";
            $retval .= "</td>";
            if (!($chkcount % $this->rowsize))
                $retval .= "</tr><tr>";
        }
        $retval .= "</tr></table>";
        $retval .= "</td></tr>";
        $retval .= "</tr></table></td>";
        return $retval;
    }
}
