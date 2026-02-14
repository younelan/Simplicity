<?php

namespace Opensitez\Simplicity\Widgets;
use Opensitez\Simplicity\MSG;

class Select extends \Opensitez\Simplicity\FormField
{
    
    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::onComponentLoad:
                $this->framework->register_type('widget', 'select');
                break;
        }
        return parent::on_event($event);
    }
    function render_field($data=[]) {
        $name = $data['name'] ?? $this->name;
        $default = $data['default'] ?? "";
		$input_name = $name ? "name='$name'" : "";
		$input_id = $name ? "id='$name'" : "";

        $retval = "<select $input_id $input_name>\n";
        foreach ($data['values'] ?? [] as $key => $value) {

            $selected = $key == $default ? "selected" : "";
            //$value = is_array($value) ? $value['name'] : $value;
            $retval .= "<option value='$key' $selected>$value</option>\n";

        }
        $retval .= "</select>";

        return $retval;
    }
    function render($app=[])
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
