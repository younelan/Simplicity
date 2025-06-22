<?php

namespace Opensitez\Simplicity\Plugins;

use \Smarty;

class SmartyTemplate extends \Opensitez\Simplicity\Plugin
{
    protected $template_engine = null;
    protected $vars = [];
    var $left_delim = "{{\$";
    var $right_delim = "}}";
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
        $this->template_engine = new Smarty();
        $this->engine_folder = dirname(__FILE__);

        $this->template_engine->setTemplateDir("$this->engine_folder/templates")
            ->setCompileDir("$this->engine_folder/templates_c")
            ->setCacheDir("$this->engine_folder/cache");
        $this->template_engine->setCacheLifetime(3);

        $this->template_engine->left_delimiter = '{{';
        $this->template_engine->right_delimiter = '}}';

        $this->template_engine->setCaching(Smarty::CACHING_LIFETIME_CURRENT);
        $this->template_engine->setCompileCheck(false);

        return $this;
    }
    function set_file($fname)
    {
        self . $template = $fname;
    }
    function assign($var, $value)
    {
        $this->vars[$var] = $value;
        $this->template_engine->assign($var, $value);
    }
    function assign_array($var_array)
    {
        foreach ($var_array as $var => $value) {
            $this->template_engine->assign($var, $value);
        }
    }
    function render($master)
    {
        $this->master = $master;
        $this->template_engine->display("string:" . $this->master);
    }
}
