<?php
namespace Opensitez\Simplicity;

class Base
{
    protected $config_object = null;
    private $data = [];
    function __construct($config_object=null)
    {
        $this->config_object = $config_object;
    }
    function translate_page($page, $lang = "")
    {
        
        $lang = "fr";
        if($this->config_object) {
            $lang = $this->config_object->get("lang") ?? "fr";
        }
        $translations = $this->config_object->get("translations") ?? [];
        if (!isset($translations[$lang])) {
            $values = array_keys($translations['fr']);
            $translations = array_combine($values, $values);
        } else {
            $translations = $translations[$lang] ?? $translations['en'] ?? [];
        }
        $template = new \Opensitez\Simplicity\SimpleTemplate();
        $page = $template->substitute_vars($page, $translations ?? []);

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
    function get_translation($i18nstr, $lang = "")
    {
        if (!$lang) {
            $lang = "fr";
            if($this->config_object) {
                $lang = $this->config_object->get("lang") ?? "fr";
            }
        }
        $translations = $this->config['translations'] ?? [];
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
