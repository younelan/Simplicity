<?php
    namespace Opensitez\Simplicity\Plugins;

    use Opensitez\Simplicity\MSG;

    class Block extends \Opensitez\Simplicity\Plugin {
        public $name="Block";
        public $block_options=[];
        public $block_name="";
        public $block_type="block";
        public $content_type = "text"; // Default content type for blocks

        public $default = [
            'encoding' => 'utf-8',
            'content-type' => 'html'
        ]; // Default options for blocks
        public $text_block=false;
        public $description="Implements a basic block";
        public function set_block_options($options) {
            $this->block_options = $options;
            $this->block_name = $options['name']??'undefined';
            $this->block_type = $options['type'] ?? 'block';
            $this->content_type = $options['content-type']??"text";
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

            $content_type = $options['content-type'] ?? $this->default['content-type'];
            //print $content_type;exit;
            // Try to get a registered block type plugin
            $block_plugin = $this->plugins->get_registered_type('blocktype', $content_type);
            
            if ($block_plugin && method_exists($block_plugin, 'render')) {
                // Create block config structure
                $block_config = ['content' => $text];
                // Merge in any additional options as block config
                $block_config = array_merge($block_config, $options);
                return $block_plugin->render($block_config, $options);
            }

            // Fallback to default text handling
            if (is_array($text)) {
                $text = implode("\n", $text);
            }
            
            return $text;
        }
        function render($app) {

            $retval = "";
            $i18n = $this->plugins->get_plugin('i18n');
            $section=$this->block_options;
            $this->content_type = $app['content-type'] ?? 'html';
            $this->block_type = $app['type'] ?? 'block';
            $fname = $app['file'] ?? '';
            $blocklink=$section['link']??"";
            $blockclass=$section['class']??"block block-$this->block_name";
            $retval = "";
            $blockoptions = $this->default;

            if(isset($section['title'])) {
                $cur_title=$i18n->get_i18n_value($section['title']);
                if($blocklink) {
                    $retval .= "<h2 class='block-title'><a href='$blocklink'>" . $cur_title. "</a></h2>";
                } else {
                    $retval .= "<h2 class='block-title'>" . $cur_title. "</h2>";
                }
            }
            /* render the content */
            // First try to get a registered block type plugin
            $datafolder = $this->config_object->get('paths')['datafolder'] ?? '';
           
            $content = file_get_contents("$datafolder/$fname");
            $app['content'] = $content;

            $block_plugin = $this->plugins->get_registered_type('blocktype', $this->content_type);
            if ($block_plugin && method_exists($block_plugin, 'render')) {
                $retval .= $block_plugin->render($app, $blockoptions);
  
            } else {
                // Fallback to checking for a named plugin (legacy support)
                $current_plugin = $this->plugins->get_plugin($this->content_type); 
                if($current_plugin ) {
                    $plugin_content =$current_plugin->on_render_page($section);
                    $retval .= $plugin_content;
                } else {
                    // Final fallback to legacy switch statement
                    switch($this->content_type) {
                        case "text":
                            $options = ['content-type'=>$section['content-type']??"html"];

                            $tmpcontent = $section['content'] ?? $section['text'] ?? "";
                            $tmpcontent = $i18n->get_i18n_value($tmpcontent);
                            if(!is_array($tmpcontent)) {
                                $tmpcontent = [ $tmpcontent];
                            }
                            $tmpcontent = implode("\n",$tmpcontent);
                            $parsed_content = $this->render_insert_text($tmpcontent,$options,$section);
                            $retval .= $parsed_content;
                            break; 
                        case "include":
                            $options = ['content-type'=>$section['content-type']??"html"];
                            $incfile = $section['file']??"";
                            // Use the include block type plugin
                            $include_plugin = $this->plugins->get_registered_type('blocktype', 'include');
                            if ($include_plugin) {
                                $retval .= $include_plugin->render($incfile, $options);
                            }
                            break; 
                    }
                }
            }
            if($retval) {
                $retval = "<div class='$blockclass'>\n" . $retval . "</div>\n";
            }
            return $retval;

        }
        function on_render_page($app) {
            // This method is intentionally left empty, as the render logic is handled in the render method
            return $this->render($app);
        }
    }
