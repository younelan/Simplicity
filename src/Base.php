<?php
namespace Opensitez\Simplicity;

class Base
{
    private $config = [];
    private $data = [];
    function __construct($config=[])
    {
        $this->config = $config;
    }
    function translate_page($page, $lang = "")
    {
        $config = $this->config;
        $lang = $config['lang'] ?? "fr";
        if (!isset($this->config['translations'][$lang])) {
            $values = array_keys($this->config['translations']['fr']);
            $translations = array_combine($values, $values);
        } else {
            $translations = $this->config['translations'][$lang] ?? $this->config['translations']['en'] ?? [];
        }
        $template = new \Opensitez\Simplicity\SimpleTemplate();
        $page = $template->substitute_vars($page, $translations ?? []);

        return $page;
    }
    function get_translation($i18nstr, $lang = "")
    {
        if (!$lang) {
            $lang = $this->config['lang'] ?? "fr";
        }
        $retval = $this->config['translations'][$lang][$i18nstr] ?? $i18nstr;
        return $retval;
    }
}
