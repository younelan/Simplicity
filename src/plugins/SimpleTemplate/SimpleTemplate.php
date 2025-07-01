<?php

namespace Opensitez\Simplicity\Plugins;

use Opensitez\Simplicity\MSG;

class SimpleTemplate extends \Opensitez\Simplicity\Plugin 
{
    private $template_engine;
    private $engine_folder;
    protected $vars = [];
    var $left_delim = "{{";
    var $right_delim = "}}";
    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::PluginLoad:
                $this->framework->register_type('templateengine', 'simpletemplate');
                break;
        }
        return parent::on_event($event);
    }
    public function getLeftDelim()
    {
        return $this->left_delim;
    }
    public function getRightDelim()
    {
        return $this->right_delim;
    }

    function engine_init()
    {
        $this->engine_folder = dirname(__FILE__);
        $this->template_engine = new \Opensitez\Simplicity\SimpleTemplate();
        $this->template_engine->setLeftDelim($this->left_delim);
        $this->template_engine->setRightDelim($this->right_delim);
        $this->engine_folder = dirname(__FILE__);
        return $this;
    }
    function set_file($fname)
    {
        self . $template = $fname;
    }
    function assign($var, $value)
    {
        $this->vars[$var] = $value;
    }
    function assign_array($var_array)
    {
        foreach ($var_array as $var => $value) {
            $this->template_engine->assign($var, $value);
        }
    }
    function render($master, $show = false)
    {
        //print $show;exit;
        $template = new \Opensitez\Simplicity\SimpleTemplate();
        $template_string = $master;
        $rendered = $template->render($template_string, $this->vars);

        if ($show) {
            echo $rendered;
            return $rendered;
        } else {
            return $rendered;
        }
    
    }
}
