<?php
    namespace Opensitez\Simplicity;

    use Opensitez\Simplicity\MSG;

    class Block extends \Opensitez\Simplicity\Plugin {
        public $name="Block";
        public $block_options=[];
        public $block_name="";
        public $block_type="block";
        protected $content_type = "text"; // Default content type for blocks
        protected $options = [];

        public $default = [
            'encoding' => 'utf-8',
            'content-type' => 'html'
        ]; // Default options for blocks
        public $text_block=false;
        public $description="Implements a basic block";

        public function set_block_options($options) {
            $options['name'] = $options['name'] ?? 'undefined';
            $options['type'] = $options['type'] ?? 'block';
            $options['content-type'] = $options['content-type'] ?? 'text';
            $this->set_options($options);
        }
        function on_event($event)
        {
            switch ($event['type']) {
                case MSG::PluginLoad:
                    // Register this plugin as a route type handler for redirects
                    $this->plugins->register_type('sectiontype', 'block');
                    $this->plugins->register_type('sectiontype', 'include');
                    $this->plugins->register_type('routetype', 'block');
                    $this->plugins->register_type('routetype', 'include');
                    break;
            }
            return parent::on_event($event);
        }
        
        /*here for legacy until other classes stop using it*/
        function render_insert_text($text,$options=[]) {

            $content_type = $options['content-type'] ?? $this->options['content-type'] ?? 'html';

            $block_plugin = $this->plugins->get_registered_type('blocktype', $content_type);
            
            if ($block_plugin && method_exists($block_plugin, 'render')) {
                $block_config = ['content' => $text];
                $block_config = array_merge($block_config, $options);
                return $block_plugin->render($block_config, $options);
            }

            // Fallback to default text handling
            if (is_array($text)) {
                $text = implode("\n", $text);
            }
            
            return $text;
        }

        function fetch_file($incfile,$block_config=[])
        {
            $i18n = $this->plugins->get_plugin('i18n');
            $paths = $this->config_object->get('paths');

            $incfile = $block_config['file'] ?? $block_config['content'] ?? $block_config;

            if ($i18n) {
                $incfile = $i18n->get_i18n_value($incfile);
            }

            $found = false;

            $file_path = $paths["datafolder"] . "/" . $incfile;

            if ($i18n) {
                foreach ($i18n->accepted_langs() as $lang => $lang_details) {
                    if ((ctype_alpha($lang) && strlen($lang) == 2) && is_file($file_path . ".$lang")) {
                        $fcontents = @file_get_contents($file_path . ".$lang");
                        $found = true;
                        break;
                    }
                }
            }

            if (!$found) {
                $fcontents = @file_get_contents($paths["datafolder"] . "/" . $incfile);
            }

            return $fcontents;
        }
        function on_render_section($app) {
            $options = $this->block_options;

            return $this->render($options);
        }   
        function render($app = null) {
            $defaults = $this->config_object->get('defaults');
            if (!$app) {
                $app = $this->options;
            }

            $vars = [
                'block' => $app
            ];
            $retval = "";
            $i18n = $this->plugins->get_plugin('i18n');
            $app['section'] = $app['section'] ?? $defaults['section'] ?? 'content';
            $block_name = $app['name'] ?? "undefined";
            $content_type = $app['content-type'] ?? $defaults['content-type'] ?? 'text';

            $block_type = $app['type'] ?? 'block';
            $blockclass="block block-$block_name block-$block_type";
            if($app['file']??false) {
                $fcontents = $this->fetch_file($app['file'], $app);
                if ($fcontents) {
                    $app['content'] = $fcontents;
                }
            }
            $fname = $app['file'] ?? '';
            $blocklink=$app['link']??"";
            $retval = "";
            $blockoptions = $this->default;
            if(isset($app['section']['title'])) {
                $cur_title=$i18n->get_i18n_value($app['title']);
                if($blocklink) {
                    $retval .= "<h2 class='block-title'><a href='$blocklink'>" . $cur_title. "</a></h2>";
                } else {
                    $retval .= "<h2 class='block-title'>" . $cur_title. "</h2>";
                }
            }
            /* render the content */
            // First try to get a registered block type plugin
            if($app['type']== 'include') {
                $datafolder = $this->config_object->get('paths')['datafolder'] ?? '';
                $content = $this->fetch_file("$datafolder/$fname", $app);
                $app['content'] = $app['content'] ?? $content;            
            }
            $datafolder = $this->config_object->get('paths')['datafolder'] ?? '';
            
            $block_plugin = $this->plugins->get_registered_type('blocktype', $content_type);
            // print get_class($block_plugin) . " - " . $this->content_type . "\n";
            // print "Types: " . print_r($this->plugins->get_registered_type_list("blocktype"), true) . "\n";
            if ($block_plugin && method_exists($block_plugin, 'render')) {
                //print "Rendering block type: $content_type\n";
                $retval .= $block_plugin->render($app, $blockoptions);
                //print $retval . "\n";
  
            } else {
                print "No block plugin found for type: $content_type\n";
                $block_plugin = $this->plugins->get_registered_type('blocktype', "text");
                if ($block_plugin && method_exists($block_plugin, 'render')) {
                    $app['content-type'] = $app['content-type'] ?? 'text';
                    $retval .= $block_plugin->render($app, $blockoptions);
                } else {
                    $retval .= "";
                }
            }
            // else {
            //     $current_plugin = $this->plugins->get_plugin($this->content_type); 
            //     if($current_plugin ) {
            //         $plugin_content =$current_plugin->on_render_page($section);
            //         $retval .= $plugin_content;
            //     } else {
            //         // Final fallback to legacy switch statement
            //         switch($this->content_type) {
            //             case "text":
            //                 $options = ['content-type'=>$section['content-type']??"html"];

            //                 $tmpcontent = $section['content'] ?? $section['text'] ?? "";
            //                 $tmpcontent = $i18n->get_i18n_value($tmpcontent);
            //                 if(!is_array($tmpcontent)) {
            //                     $tmpcontent = [ $tmpcontent];
            //                 }
            //                 $tmpcontent = implode("\n",$tmpcontent);
            //                 $parsed_content = $this->render_insert_text($tmpcontent,$options,$section);
            //                 $retval .= $parsed_content;
            //                 break; 
            //             case "include":
            //                 $options = ['content-type'=>$section['content-type']??"html"];
            //                 $incfile = $section['file']??"";
            //                 // Use the include block type plugin
            //                 $include_plugin = $this->plugins->get_registered_type('blocktype', 'include');
            //                 if ($include_plugin) {
            //                     $retval .= $include_plugin->render($incfile, $options);
            //                 }
            //                 break; 
            //         }
            //     }
            // }
            if($retval) {
                $retval = "<div class='$blockclass'>\n" . $retval . "</div>\n";
            }
            return $retval;

        }
        function on_render_page($app) {
            return $this->render($app);
        }
    }
