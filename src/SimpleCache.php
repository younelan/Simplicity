<?php

namespace Opensitez\Simplicity;

class SimpleCache
{
    private $options = [];
    function setOptions($options)
    {
        $this->options = $options;
    }
    function getFileName($url)
    {

        $cache_dir = $this->options['cache-dir'] ?? $this->config_object->get('paths.cache') ?? (__DIR__ . "/cache");
        $urlmd5 = md5($url);
        $cache_file = "$cache_dir/$urlmd5.txt";

        return $cache_file;
    }
    function isCacheCurrent($url, $timeout)
    {
        $cache_file = $this->getFileName($url);
        $seconds = $this->options['seconds'] ?? 3;
        $timeout = $this->options['timeout'] ?? 300;

        if (file_exists("$cache_file")) {
            $timediff = time() - filemtime("$cache_file");
            if ($timediff > $seconds) {
                return false;
            } else {
                return true;
            }
        }
    }
    function put($url, $page_contents)
    {
        $cache_file = $this->getFileName($url);
        file_put_contents($cache_file, $page_contents);
    }
    function get($url)
    {
        $cache_file = $this->getFileName($url);
        $page_contents = file_get_contents($cache_file);
        return $page_contents;
    }
}
