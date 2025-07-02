<?php

namespace Opensitez\Simplicity\Plugins;
use Opensitez\Simplicity\MSG;

class Text extends \Opensitez\Simplicity\FormField
{
    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::PluginLoad:
                // Register this plugin as a route type handler for blogs
                $this->framework->register_type('widget', 'text');
                break;
        }
        return parent::on_event($event);
    }
    function render_field($data=[]) {
        $name = $data['name'] ?? $this->name;
        $default = $data['default'] ?? "";
		$input_name = $name ? "name='$name'" : "";
		$input_id = $name ? "id='$name'" : "";

        $retval = "<input type='text' $input_id $input_name value='" . htmlspecialchars($default) . "'>";
        return $retval;
    }
    function render($app=[])
    {
        $theError = $this->render_error();
        $label = $this->get_i18n_value($this->label, $this->lang);
        $name = $this->name;
        $colspan = $this->colspan;

        $retval = "";
        $retval .= "<td $colspan><table><tr>\n";
        $retval .=  "<td>$label </td><td><input type=text name=\"$name\" value=\"" . $this->value . "\"> $theError</td>";
        $retval .= "\n</tr></table></td>\n\n";
        return $retval;
    }
}
