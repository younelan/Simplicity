<?php

namespace Opensitez\Simplicity\Plugins;

class I18n extends \Opensitez\Simplicity\Plugin
{
    function accepted_langs()
    {
        $langs = [];
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            foreach (explode(",", $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? []) as $lang) {
                $langs[strtolower(substr($lang, 0, 2))] = $lang;
            }
        }

        return $langs;
    }
    function get_i18n_value($str, $defaultlang = "", $debug = false)
    {
        if ($defaultlang && isset($str[$defaultlang])) {
            return $str[$defaultlang];
        }
        $retval = "";
        $langs = $this->accepted_langs();

        // if($debug) {
        //     print "no $defaultlang";
        // }


        if (!is_array($str) || !($str)) {
            $retval = $str;
            return $retval;
        } else {
            $found = false;
            foreach (array_keys($langs) as $idx) {
                if (isset($str[$idx])) {
                    $retval = $str[$idx];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                if (isset($str["default"])) {
                    $retval = $str["default"];
                } else if (isset($str["en"])) {
                    $retval = $str["en"];
                } else {
                    $retval = $str;
                }
            }
        }
        return $retval;
    }
}

//$i18n=new I18n($config_object);
// $i18n->set_config($i18n);
