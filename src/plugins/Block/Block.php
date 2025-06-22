<?php
    namespace Opensitez\Simplicity\Plugins;

    use Opensitez\Simplicity\MSG;

    class Block extends \Opensitez\Simplicity\Plugin {
        public $name="Block";
        public $block_options=[];
        public $block_name="";
        public $block_type="";
        public $text_block=false;
        public $description="Implements a basic block";
        public function set_block_options($options) {
            $this->block_options = $options;
            $this->block_name = $options['name']??'undefined';
            $this->block_type = $options['type']??"text";
            //print "<div>new block of {$this->block_name} of type {$this->block_type}</div>";
        }
        function on_event($event)
        {
            switch ($event['type']) {
                case MSG::PluginLoad:
                    // Register this plugin as a route type handler for redirects
                    $this->plugins->register_type('routetype', 'block');
                    break;
            }
            return parent::on_event($event);
        }
        
        /*here for legacy until other classes stop using it*/
        function render_insert_text($text,$options=[]) {
            $default = [
                'encoding' => 'utf-8',
                'content-type' => 'html'
            ];

            $content_type = $options['content-type'] ?? $default['content-type'];
            
            // Try to get a registered block type plugin
            $block_plugin = $this->plugins->get_registered_type('blocktype', $content_type);
            if ($block_plugin && method_exists($block_plugin, 'render')) {
                return $block_plugin->render($text, $options);
            }

            // Fallback to default text handling
            if (is_array($text)) {
                $text = implode("\n", $text);
            }
            
            return $text;
        }
        function render($app) {
            $i18n = $this->plugins->get_plugin('i18n');
            $section=$this->block_options;
            $blocklink=$section['link']??"";
            $blockclass=$section['class']??"block block-$this->block_name";
            $retval = "";

            if(isset($section['title'])) {
                $cur_title=$i18n->get_i18n_value($section['title']);
                if($blocklink) {
                    $retval .= "<h2 class='block-title'><a href='$blocklink'>" . $cur_title. "</a></h2>";
                } else {
                    $retval .= "<h2 class='block-title'>" . $cur_title. "</h2>";
                }
            }

            /* render the content */
            $current_plugin = $this->plugins->get_plugin($this->block_type); 
            $current_path=$app['route']??"default";

            $paths = $this->config_object->get('paths');
            if($current_plugin ) {
                //$current = $this->config_object->get('current');
                //$config['current']=$this->block_options;
                $block_options=$app["route"];
                $plugin_content =$current_plugin->on_render_page($section);
                $retval .= $plugin_content;
            } else {

                switch($this->block_type) {
                    case "text":
                        $options = ['content-type'=>$section['content-type']??"html"];

                        $tmpcontent = $section['content']??"";
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
            if($retval) {
                $retval = "<div class='$blockclass'>\n" . $retval . "</div>\n";
            }
            return $retval;

        }
    }
