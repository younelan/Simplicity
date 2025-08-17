<?php
namespace Opensitez\Simplicity;

class Base
{
    protected $config_object = null;
    private $data = [];
    protected $leftdelim = "{{";
    protected $rightdelim = "}}";
    protected $options = [];
    // Debug function with optional debug level
    public function debug($msg, $level = 2) {
        // For now, just print anything
        $current_level = $this->config_object->get('debug.level', 0);
        $current_level = 0;
        if ($current_level>=$level) {
            echo $msg;
        }
    }
    function __construct($config_object=null)
    {
        if(!$config_object) {
            $config_object = new \Opensitez\Simplicity\Config();
        } elseif (is_array($config_object)) {
            $config_object = new \Opensitez\Simplicity\Config($config_object);
        } elseif (is_object($config_object)) {
            $config_object = $config_object;
        }
        $this->config_object = $config_object;

    }
    // Substitute variables in the template
    public function substitute_vars($content, $vars, $blocks = [])
    {
        $leftDelim = preg_quote($this->leftdelim, '/'); // Escape special characters for regex
        $rightDelim = preg_quote($this->rightdelim, '/'); // Escape special characters for regex
        $pattern = "/{$leftDelim}([^}]+){$rightDelim}/"; // Match anything between delimiters

        $content = preg_replace_callback($pattern, function ($matches) use ($vars) {
            $varName = $matches[1];
            $keys = explode('.', $varName);

            $value = $vars;
            foreach ($keys as $key) {
                if (is_array($value) && array_key_exists($key, $value)) {
                    $value = $value[$key];
                    //print " found $key <br/>\n";
                } else {
                    //print " not found $key <br/>\n";
                    return $matches[0]; // Return original placeholder if no match found
                }
            }
            return is_string($value) || is_numeric($value) ? $value : $matches[0];
        }, $content??"");

        return $content;
    }
    public function set_options ($options) {
        $this->options = $options;
    }


    function replace_paths($string)
    {
        $paths = $this->config_object->get('paths');

        return $this->substitute_vars($string, $paths);        
    }
    function validate_folder_path($path, $maxdepth = 3, $maxpartlength = 50, $options = [])
    {

        $maxpartlength = $options['maxparthlength'] ?? 50;
        $default = [
            'matchstring' => "/^[a-zA-Z0-9][a-zA-Z0-9\'\ \+\.\(\)\-\_]{0,$maxpartlength}$/",
            'maxdepth' => 3,
        ];
        $maxdepth = $options['maxdepth'] ?? $default['maxdepth'];
        $matchstring = $matchstring ?? $default['matchstring'];
        //print $matchstring;
        $path = trim($path, "/");
        if (!$path)
            $path = "";
        $idx = 0;
        foreach (explode("/", $path) as $part) {
            $idx += 1;
            if (!preg_match($matchstring, $part)) {
                $path = "";
            }
        }
        if ($idx > $maxdepth) {
            $path = "";
        }

        return $path;
    }
    public function isLoggedIn($session=false) {
        if (!$session) {
            $session = $_SESSION ?? [];
        }
        return isset($session["login"]) && isset($session['password']) ;
        //to do reimplement if passwords changed during session
        //&& isset($this->users[$session["login"]]) && $session['password'] === $this->users[$session["login"]][$this->password_field];
    }
    function load_template($file,$paths = false)
    {
        // print "Loading template file: $file\n";
        // print_r($paths);
        // exit;
        if($paths) {
            foreach ($paths as $path) {
                if (is_file($path . "/" . $file)) {
                    $full_path = $path . "/" . $file;
                    $template_contents = @file_get_contents($full_path);
                    return $template_contents;
                    break;
                }
            }
        }
        if($this->config_object) {
            $paths = $this->config_object->get('paths');
            $syspath = $this->config_object->get('system.paths', []);
            $core_templates = $this->config_object->get('core-templates', __DIR__ . "/templates" . "/");
            $datafolder = $this->config_object->get('paths.datafolder', __DIR__ . "/data" . "/");
            $site_template_path = $datafolder . "/templates" . "/" . $file;

            $full_path = $core_templates     . "/" . $file;
            $sys_template_path = $syspath['templates'] . "/" . $file;
        } else {
            $core_templates = __DIR__ . "/templates" . "/";
            $paths = [];
            $syspath = [];
            $sys_template_path =  $core_templates . "/" . $file;
            $full_path =  $core_templates . "/" . $file;
        }
        $default_folder = $paths['datafolder'] ?? __DIR__ . "/data" . "/";


        if (is_file($site_template_path)) {
            //print "Loading site template file: $site_template_path\n";
            $full_path = $site_template_path;
            $template_contents = @file_get_contents($full_path);
        } 
        elseif (is_file($full_path)) {
            //print "Loading template file: $full_path\n";
            $template_contents = @file_get_contents($full_path);

        } 
        elseif (is_file($sys_template_path)) {
            $full_path = $sys_template_path;
            //print "Loading system template file: $full_path\n";
            $template_contents = @file_get_contents($full_path);
        } elseif (is_file($default_folder . "/" . $file)) {
            //print "Loading default template file: $default_folder/$file\n";
            $full_path = $default_folder . "/" . $file;
            $template_contents = @file_get_contents($full_path);
        } else {
            $template_contents = "";
            //print "No template file found for $file\n";
        }
        return $template_contents;
    }
    function translate_page($page, $lang = "")
    {
        $lang = "fr";
        if($this->config_object) {
            $lang = $this->config_object->get("site.lang") ?? "fr";
        }
        $translations = $this->config_object->get("translations") ?? [];
        if (!isset($translations[$lang])) {
            $values = array_keys($translations['fr']);
            $translations = array_combine($values, $values);
        } else {
            $translations = $translations[$lang] ?? $translations['en'] ?? [];
        }
        $template = new \Opensitez\Simplicity\SimpleTemplate();
        $page = $this->substitute_vars($page, $translations ?? []);

        return $page;
    }
    /**
     * Get a translation for a given string in the specified language.
     * If no language is specified, defaults to French ('fr').
     *
     * @param string $i18nstr The string to translate.
     * @param string $lang The language code (e.g., 'en', 'fr'). Defaults to 'fr'.
     * @return string The translated string or the original string if no translation is found.
     */
    function get_translation($i18nstr, $translations = false, $lang = false)
    {
        if (!$translations) {
            $translations = $this->config_object->get("translations") ?? [];
        }
        if (!$lang) {
            $lang = "fr";
            if($this->config_object) {
                $lang = $this->config_object->get("lang") ?? "fr";
            }
        }
        $retval = $translations[$lang][$i18nstr] ?? $i18nstr;
        return $retval;
    }
    function get_menus()
    {
        $menus = [
            // "menuname" => [
            //     "text"=>"Menu name",
            //     "weight"=> 0,
            //     "children"=> [
            //        "menuentry"=> ["component"=>"gallery","page"=>"pageid","text"=>"Menu Text","category"=>"all"],
            //     ]
            // ],

        ];
        return $menus;
    }

    /**
     * Validate that a folder name is safe (no directory traversal)
     * @param string $folderName The folder name to validate
     * @return bool True if safe, false otherwise
     */
    private function isValidFolderName(string $folderName): bool
    {
        // Check for empty string
        if (empty($folderName)) {
            return false;
        }
        
        // Check for directory traversal attempts
        if (strpos($folderName, '..') !== false || 
            strpos($folderName, '/') !== false || 
            strpos($folderName, '\\') !== false) {
            return false;
        }
        
        // Check for hidden files/folders
        if (strpos($folderName, '.') === 0) {
            return false;
        }
        
        return true;
    }
    public function add_block($block)
    {
        if (!$this->valid_var_name($block['name'])) {
            print "Invalid block name: " . $block['name'] . " (must match pattern [a-zA-Z][a-zA-Z0-9-]*)\n";
            return;
        }
        $defaults = $this->config_object->get('defaults');
        $block['type'] = $block['type'] ?? 'text';
        $block['content'] = $block['content'] ?? '';
        $block['content-type'] = $block['content-type'] ?? 'html';
        $block['template'] = $block['template'] ?? 'default.tpl';

        $block_name = $block['name'] ;
        $section = $block['section'] ?? $defaults['section'] ?? "content";
        if (!$this->valid_var_name($section)) {
            print "Invalid section name: " . $section . " (must match pattern [a-zA-Z][a-zA-Z0-9-]*)\n";
            $section = 'content';
        }
        $block['section'] = $section;

        if (!$this->config_object->get('site.sections.' . $section)) {
            $this->add_section($section);
        }

        $block_instance = new Block($this->config_object);
        print "Adding block: " . $block_name . " to section: " . $section . "\n";
        $block_instance->set_block_options($block);
        $block_instance->set_framework($this->framework);

        $this->config_object->set("site.blocks.$block_name", $block_instance);
    }

    public function add_blocks($blocks = false, $section = false)
    {
        $defaults = $this->config_object->get('defaults');
        $current_site = $this->config_object->get('site');
        if(!$section) {
            $section = $current_site['default-section'] ?? $defaults['default-section'] ?? "content";
        }
        $section = $section ?? $this->default_section;

        if (!$blocks) {
            $blocks = $current_site['blocks'] ?? [];
        }
        foreach ($blocks ?? [] as $block_name => $block_content) {
            print "Adding block: " . $block_name . " to section: " . $section . " \n";
            $block_content['name'] = $block_name;
            $this->add_block($block_content);
        }
    }
    function render_block_list($inserts, $app = null)
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
        foreach ($inserts as $id => $incblock) {
            if (!is_array($incblock)) {
                $incblock = ["content" => $incblock];
            }
            $inctype = $incblock['type'] ?? "block";

            $current_component = $this->framework->get_registered_type("blocktype", $inctype);
            if(!$current_component) {
                $current_component = $this->framework->get_component("block");
            }
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
            }
        }
        return $content;
    }
}
