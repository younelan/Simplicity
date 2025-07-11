<?php
namespace Opensitez\Simplicity;

class Base
{
    protected $config_object = null;
    private $data = [];
    protected $leftdelim = "{{";
    protected $rightdelim = "}}";
    protected $options = [];
    function __construct($config_object=null)
    {
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
    function load_template($file,$default_folder = false)
    {
        $paths = $this->config_object->get('paths');
        $syspath = $this->config_object->get('system.paths');
        
        $site_template_path = $paths['datafolder'] . "/templates" . "/" . $file;
        $full_path = $paths['core-templates'] . "/" . $file;

        $sys_template_path = $syspath['templates'] . "/" . $file;

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
        //$translations = $this->config['translations'] ?? [];
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
            //        "menuentry"=> ["plugin"=>"gallery","page"=>"pageid","text"=>"Menu Text","category"=>"all"],
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

}
