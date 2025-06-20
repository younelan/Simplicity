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
}
