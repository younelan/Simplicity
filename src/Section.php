<?php

namespace Opensitez\Simplicity;
use Opensitez\Simplicity\MSG;


class Section extends \Opensitez\Simplicity\Component
{
    private $contents = [];
    private $style = "";
    private $class = "section";
    private $section_name = "content";
    private $blocks = [];
    private $template = "";
    private $section_options = [];
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
            $output .= $block->on_render_section($app);
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
    function new_render_section_contents($inserts,$app=null) {
        $content = "";
;
        $block_component = $this->framework->get_component("block");
        foreach ($inserts as $id=>$block) {
            print "$id<br/>" . print_r($block,true);
            $content .= $block_component->on_render_section($block);
        };        
        return $content;
    }
    function render_section_contents($inserts, $app = null)
    {
        if(!$app) {
            $app = $this->config_object->get('site.current-route');
        }

        $style = $app['style'] ?? $this->style;
        $class = $app['class'] ?? "";
        $class = "section " . $this->class;
        $block_component = $this->framework->get_component("block");
        $paths = $this->config_object->get('paths');
        $content = "";

        $i18n = $this->framework->get_component('i18n');
        if (!is_array($inserts)) {
            $inserts = [$inserts];
        }
        if (!is_array($inserts)) {
            $inserts = [["content" => $inserts]];
        }
        foreach ($inserts as $id => $incblock) {
            if (!is_array($incblock)) {
                $incblock = ["content" => $incblock];
            }
            //print "$id<br/>";
            $inctype = $incblock['type'] ?? "block";

            $current_component = $this->framework->get_component($inctype);
            if (!isset($incblock['type'])) {
                $incblock = ['content' => $incblock];
                $incblock['type'] = $inctype ?? "block";
            }
            $gallerylink = $incblock['link'] ?? "";
            if (isset($incblock['title'])) {
                $cur_title = $i18n->get_i18n_value($incblock['title']);
                if ($gallerylink) {
                    $content .= "<h2 class='block-title'><a href='/'>" . $cur_title . "</a></h2>";
                } else {
                    $content .= "<h2 class='block-title'>" . $cur_title . "</h2>";
                }
            }

            if ($current_component) {
                $component_content = $current_component->on_render_page($incblock);
                $content .= $component_content;
            } else {

                switch (strtolower($inctype)) {

                    case "include":
                        $incname = $i18n->get_i18n_value($incblock['file'] ?? "");
                        $full_path = $paths["datafolder"] . "/" . $incname;
                        $options = ["content-type" => $incblock['content-type'] ?? "html"];
                        if (is_file($full_path)) {
                            $fcontents = @file_get_contents($full_path);
                            $fcontents = $block_component->render_insert_text($fcontents ?? "", $options, $incblock);
                            $content .= $fcontents;
                        }
                        break;
                    default:
                    case "text":
                        $options = ["content-type" => $incblock['content-type'] ?? "html", 'style' => $style, $class];
                        $text_content = $incblock['content'] ?? $incblock;
                        $text_content = $i18n->get_i18n_value($text_content);

                        $content .= $block_component->render_insert_text($text_content, $options, $incblock);
                        break;
                }
            }
        }
        return $content;
    }
}
