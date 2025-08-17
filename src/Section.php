<?php

namespace Opensitez\Simplicity;
use Opensitez\Simplicity\MSG;

class Section extends \Opensitez\Simplicity\Component
{
    protected $contents = [];
    protected $style = "";
    protected $class = "section";
    protected $section_name = "content";
    protected $blocks = [];
    protected $template = "";
    protected $section_options = [];
    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::onComponentLoad:
                // Register this component as a route type handler for redirects
                $this->framework->register_type('routetype', 'section');
                break;
        }
        return parent::on_event($event);
    }
    function set_section_options($options)
    {
        $this->section_options = $options;
        //print "Setting section options: " . print_r($options, true) . "\n";
        if ($options['file']?? false) {
            $this->template = $this->load_template("sections/" . $options['file']);
        }
        $this->section_name = $options['name'] ?? 'content';
        $this->class = $options['class'] ?? "";
        $this->style = $options['style'] ?? "";
    }
    function set_blocks($blocks)
    {
        $this->blocks = $blocks;
    }
    function on_render_page($app)
    {
        $style = $this->style;

        $output = "";
        if (is_array($style)) {
            $style = implode("\n", $style);
        }
        $output = "<div id='$this->section_name' class='section $this->class'>";
        //$output .= "<h1 class='footer'>section $this->section_name</h1>";
        foreach ($this->blocks as $block) {
            $output .= $block->on_render_block($app);
        };
        $output .= "</div>";
        return $output;
    }

    /* todo add logic to add before/after section */
    // function on_render_before() {
    //     // if(!is_array($contentbefore)) {
    //     //     $contentbefore=[$contentbefore];
    //     // }
    //     // $contentbefore= $section['content-before'] ?? [];
    //     // foreach($contentbefore as $tmp) {
    //     //     $outputs[$section] .= $tmp . "\n"; 

    //     // }            
    // }
    // function on_render_after() {
    //     $contentafter= $section['content-after'] ?? [];
    //     if(!is_array($contentafter)) {
    //         $contentafter=[$contentafter];
    //     }
    //     foreach($contentafter as $tmp) {
    //         $outputs[$section] .= $tmp ."\n"; 
    // }


}
