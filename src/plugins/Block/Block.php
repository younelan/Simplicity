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
        /*here for legacy until other classes stop using it*/
        function render_insert_text($text,$options=[]) {
            $text_block = $this->text??false;
            if(!$text_block) {
                $this->text_block = new TextBlock($this->config_object);
            }
            return $this->text_block->render_insert_text($text, $options);

        }
        function on_render_block($app) {
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

            $paths = $this->config_object->getPaths();
            if($current_plugin ) {
                $config['current']=$this->block_options;
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
                            $incfile=$section['file']??"";
                            $incfile=$i18n->get_i18n_value($incfile);
                            $found=false;
                            $file_path=$paths["datafolder"]."/" . $incfile;
                            foreach($i18n->accepted_langs() as $lang=>$lang_details) {
                                if((ctype_alpha($lang) && strlen($lang)==2) && is_file($file_path . ".$lang")) {
                                    $fcontents=@file_get_contents($file_path .".$lang");
                                    $found=true;
                                    break;
                                }
                            }
                            if(!$found) {                                    
                                $fcontents = @file_get_contents($paths["datafolder"]."/" . $incfile);
                            }                                


                            $retval.= $this->render_insert_text($fcontents,$options,$section);
                        break; 
                }
            }
            if($retval) {
                $retval = "<div class='$blockclass'>\n" . $retval . "</div>\n";
            }
            return $retval;

        }
    }
