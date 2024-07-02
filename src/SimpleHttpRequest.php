<?php

namespace Opensitez\Simplicity;

class SimpleHttpRequest
{
    function fetch_raw($url)
    {
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
    function fetch($options)
    {
        $url = $options['url'] ?? "";
        $timeout = $options['timeout'] ?? 300;

        $cache = new SimpleCache();
        $cache->setOptions($options);
        if ($cache->isCacheCurrent($url, $timeout)) {
            return $cache->get($url);
        } else {
            $page_contents = $this->fetch_raw("$url");
            $cache->put($url, $page_contents);
            return $page_contents;
        }
    }
}
