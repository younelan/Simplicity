<?php

namespace Opensitez\Simplicity\Components;

use Twig\Loader\ArrayLoader;
use Twig\Environment;
use Opensitez\Simplicity\MSG;

class TwigTemplate extends \Opensitez\Simplicity\Component 
{
    protected $template_engine = null;
    protected $vars = [];
    private $left_delim = "{{";
    private $right_delim = "}}";
    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::onComponentLoad:
                $this->framework->register_type('templateengine', 'twigtemplate');
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
    function getDefaultTemplateFile()
    {
        return "main.tpl";
    }
    function engine_init()
    {
    }
    function set_file($fname)
    {
        $this->template = $fname;
    }
    function assign($var, $value)
    {
        $this->vars[$var] = $value;
        //$this->template_engine->assign($var,$value);
    }
    function assign_array($var_array)
    {
        foreach ($var_array as $var => $value) {
            $this->template_engine->assign($var, $value);
        }
    }

    /**
     * Render a Twig template from a string with provided data.
     *
     * @param array $data     Associative array of data to pass to the template
     * @param string $template Template content as a string
     * @return string Rendered template output
     */
    function render($template, $data = false)
    {
        if (!$data) {
            $data = $this->vars;
        }

        $loader = new ArrayLoader([
            'template' => $template, // Template name 'template' with provided content
        ]);
        // Configure Twig environment with raw variable output (no escaping)
        $twig = new Environment($loader, [
            'autoescape' => false, // Set autoescape strategy (can also be set to 'true' or 'false'),
            'cache' => false, // Disable caching
        ]);
        // Render the template with the provided data
        return $twig->render('template', $data);
    }
}
