<?php

namespace Opensitez\Simplicity\Plugins;
use Opensitez\Simplicity\MSG;

class TextArea extends \Opensitez\Simplicity\FormField
{
    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::onComponentLoad:
                // Register this plugin as a route type handler for blogs
                $this->framework->register_type('widget', 'textarea');
                break;
        }
        return parent::on_event($event);
    }
    function render($app=[])
    {
        $theError = $this->render_error();
        $label = $this->get_i18n_value($this->label, $this->lang);
        $name = $this->name;
        $colspan = $this->colspan;

        $retval = "";
        $retval .= "<td $colspan><table><tr>\n";
        if (isset($this->field_def["cols"]))
            $cols = " cols=" . $this->field_defs['cols'];
        else
            $cols = "";

        if (isset($this->field_defs["rows"]))
            $rows = " rows=" . $this->field_defs['rows'];
        else
            $rows = "";

        $retval .=  "<td>$label </td><td><textarea $cols $rows name=\"$name\">" . $this->value . "</textarea> $theError</td>";
        $retval .= "\n</tr></table></td>\n\n";
    }
}
