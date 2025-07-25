<?php
    namespace Opensitez\Simplicity\Components;

    use Opensitez\Simplicity\MSG;

    class ContentProvider extends \Opensitez\Simplicity\Component {
        public $name="ContentProvider";
        public $description="Adds a blog to the site";
        var $config=[];
        var $node_types=array();
        
        function on_event($event)
        {
            switch ($event['type']) {
                case MSG::onComponentLoad:
                    // Register this component as a route type handler for blogs
                    $this->framework->register_type('routetype', 'blog');
                    $this->framework->register_type('routetype', 'content');
                    $this->framework->register_type('blocktype', 'blog');

                    break;
            }
            return parent::on_event($event);
        }
        
        function get_menus($app=[]) {
            $menus = [
                "content" => [
                    "text"=>"Content",
                    "image"=>"genimgblog.png",
                    "children"=> [
                       "blogs"=> ["component"=>"content","page"=>"default","text"=>"Blogs","category"=>"all"],
                    ]
                ],

            ];
            return $menus;            
        }
        public function render_results($results,$params=array()){
		    $block_component = $this->framework->get_component('block');
            $output='';
            $app = $this->app??[];

            $options = [
                'encoding' => $app['encoding']??'utf-8',
                'content-type' => $app['content-type']??'html'
            ];
            if(!@$params['title']) $params['title']=@$config['site_title'];
            if(!@$params['description']) $params['description']=@$this->config['site_section'];
            $content_type=$app['item']['content-type']??"html";
            if( $results && count($results)==1) {
                $params['og_title']=$results[0]['title'];

                if(strpos("<p>",$results[0]['body'])) {
                    $params['description']=htmlentities(explode('<p>', trim(strip_tags($results[0]['body'])))[0], ENT_QUOTES);
                } else {
                    $params['description']=htmlentities(preg_split("/\n\W+/", trim(strip_tags($results[0]['body'])))[0],ENT_QUOTES);
                }
            }
            $blog_title=@$this->config['site_title']=@$this->config['host'] . " - " . $params['og_title']; 
            $site_title=@ $params['og_title'];
            $this->config['blog_title']=$site_title;
            $site_name=@$this->config['site_name']; 
            $this->config['blocks']['social_block']="
                <meta property=\"og:type\" content=\"blog\"/>
                <meta property=\"og:title\" content='$blog_title' />
                <meta property=\"og:site_name\" content=\"$site_name\" />
                <meta property='og:description' content='{$params['description']}' />
                ";
            if(@$params['thumbnails'])
                $output .= "<notmeta notproperty='og:image' content='{$params[thumbnail]}' />";
            //$results=$this->get_data(array('feature'=>$feature));
            $full_path=@$this->config['blog_path'];

            if(!$full_path)
                $full_path=$this->app['name']??""; 
            $blog_path=$full_path;

            if($full_path=="/") $full_path="default/";
            if(!$results) {
                $results=[];
            }
            foreach($results as $row) {
                // Build absolute URL: scriptPath/route/slug
                $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
                $basePath = dirname($scriptName); // /impress/public
                $route = $this->app['route'] ?? $full_path; // umami
                
                // Build the full absolute path
                $blog_path = $basePath . '/' . $route . '/' . ltrim($row['slug'], '/');
                
                // Clean up any double slashes
                $blog_path = preg_replace('#/+#', '/', $blog_path);
                
                if( isset($this->node_types[intval($row['node_type'])])) {
                if(isset($this->node_types) && $this->node_types && isset($this->node_types[$row['node_type']]['fields']))
                    foreach($this->node_types[$row['node_type']]['fields'] as $field_id=>$field_name) {
                        $field_name=ucfirst($field_name);
                        //print_r($row);exit;
                        $fieldname="field$field_id";
                        //print $fieldname;   
                        $field_value=ucfirst(trim($row[$fieldname]??""));
                        if($field_value) {
                        $row['body'] .= "<p><b>$field_name:</b> $field_value</p>" ;
                        }
                    }

                }
                $row['body']=$block_component->render_insert_text($row['body'],$options);
                $output .= "<div class=blog-post>\n
    <header class='entry-header'>
    <h1 class='entry-title blog-header'>
    <a href='" . $blog_path . "'>\n" . $row['title'] . "</a></h1>
    </header>
    " . $row['body'] . "\n</div>\n";
            }
            $feature=@$this->config['feature'];
            $output .= "</div>";
            return $output;
        }

        public function on_render_page($app) {
            if(!$app) {
                $app=$this->app;
            } else {
                $this->app=$app;
            }

            //$fname=substr($_SERVER['REQUEST_URI'],strlen($app['route'])+2);
            $fname=$app['path'];
            $full_path=$app['name'] ?? $app['route'];
            $subtype=$app['subtype']??"opensite";

            // Get content provider from registry instead of hardcoded switch
            $content_provider = $this->framework->get_registered_type('contentprovider', strtolower($subtype));
            
            if ($content_provider) {
                $content_provider->connect();
                $data = $content_provider->fetch_data($app);
                $content = $this->render_results($data);
                return $content;
            } else {
                // Fallback to default OSZ content provider if available
                $default_provider = $this->framework->get_registered_type('contentprovider', 'osz');
                if ($default_provider) {
                    $default_provider->connect();
                    $data = $default_provider->fetch_data($app);
                    $content = $this->render_results($data);
                    return $content;
                }
                
                return "Content provider for type '$subtype' not found.";
            }
        }

    }

