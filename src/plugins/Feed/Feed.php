<?php

namespace Opensitez\Simplicity\Plugins;

use Opensitez\Simplicity\MSG;

class Feed extends \Opensitez\Simplicity\Plugin
{
    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::PluginLoad:
                // Register this plugin as a route type handler for redirects
                $this->plugins->register_type('routetype', 'feed');
                $this->plugins->register_type('blocktype', 'feed');
                break;
        }
        return parent::on_event($event);
    }
    
    public function on_render_page($app)
    {
        $this->app = $app;
        $subtype = isset($app['subtype']) ? $app['subtype'] : 'rss';
        $maxLength = isset($app['max-length']) ? $app['max-length'] : 200;
        $suffix = $app['suffix'] ?? "";
        $get_params = $app['vars'] ?? [];
        $limit = intval($app['limit'] ?? 0);
        if ($subtype !== 'atom' && $subtype !== 'rss') {
            return '';
        }

        $rssFeed = $this->fetch($app['url'], $get_params, $suffix);

        // If fetching the feed fails or there are no results, return an empty string
        if ($rssFeed === false || empty($rssFeed)) {
            return '';
        }
        $template = $app['template'] ?? "";
        $stripTags = isset($app['strip_tags']) ? filter_var($app['strip_tags'], FILTER_VALIDATE_BOOLEAN) : true;

        // Render the RSS feed using the template
        return $this->renderFeed($rssFeed, $template, $stripTags, $maxLength, $limit);
    }

    private function fetch($url, $get_params, $suffix)
    {
        $current_site = $this->config_object->getCurrentSite();
        $paths = $this->config_object->getPaths();
        $rf = new \Opensitez\Simplicity\SimpleHttpRequest($this->config_object);
        $cachedir = $app['vars']['cache-dir'] ?? $current_site['vars']['cache-dir'] ?? "";
        if ($cachedir) {
            $cachedir = $paths['base'] . "/" . $paths['sitepath'] . "/"  . $cachedir;
        }

        $queryString = http_build_query($get_params);
        if ($get_params) {
            $end_url = $url . "?" . $queryString . $suffix;
        } else {
            $end_url = $url  . $suffix;
        }
        $options = [
            'url' => $end_url,
            'timeout' => 10,
            'suffix' => $suffix,
            'cache-dir' => $cachedir
        ];

        $page = $rf->fetch($options);
        //print_r($page);exit;
        return $page;
    }

    private function renderFeed($rssFeed, $template, $stripTags, $maxLength = 0, $limit = 0)
    {
        $rss = simplexml_load_string($rssFeed);
        if (!isset($rss->channel) && !isset($rss->entry)) {
            return ''; // Return an empty string to indicate no data
        }
        $items = [];

        if (isset($rss->channel)) {
            // This is an RSS feed
            if (!$template) {
                $template = '<li class="rss-item"><a href="{link}">{title}</a> - {description}<br/>{date}</li>';
            }
            if (isset($rss->channel->item)) {
                $items = $rss->channel->item;
            } elseif (isset($rss->item)) {
                $items = $rss->item;
            }
        } elseif (isset($rss->entry)) {
            // This is an Atom feed
            if (!$template) {
                $template = '<li class="rss-item"><a href="{link}">{title}</a> - {summary}<br/>{date}</li>';
            }
            $items = $rss->entry;
        }
        $renderedItems = [];
        $idx = 0;
        foreach ($items as $item) {
            $idx += 1;
            if ($limit && $idx == $limit) {
                break;
            }
            $itemHtml = $this->applyTemplate($template, $this->getItemVariables($item, $stripTags, $maxLength));
            $renderedItems[] = $itemHtml;
        }

        $html = "<div class='widget rss-widget'><ul class='rss-feed'>" . implode('', $renderedItems) . "</ul></div>";
        return $html;
    }

    private function applyTemplate($template, $vars)
    {
        foreach ($vars as $key => $value) {
            $template = str_replace("{{$key}}", htmlspecialchars($value), $template);
        }

        return $template;
    }
    function summarize($text, $maxLength = 200)
    {
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        if (strlen($text) <= $maxLength) {
            return $text;
        }

        $summary = substr($text, 0, $maxLength);
        $lastPeriod = strrpos($summary, '.');
        $lastEmptyLine = strpos($summary, "\n\n");

        if ($lastPeriod !== false && ($lastEmptyLine === false || $lastPeriod > $lastEmptyLine)) {
            $summary = substr($summary, 0, $lastPeriod + 1);
        } elseif ($lastEmptyLine !== false) {
            $summary = substr($summary, 0, $lastEmptyLine);
        }

        return $summary;
    }

    // Get an associative array of variables for a feed item
    private function getItemVariables($item, $stripTags, $maxLength = 200)
    {
        $description = (string)$item->description ?? "";
        $summary = (string)$item->summary ?? "";
        if ($maxLength) {
            $description = $this->summarize($description, $maxLength);
            $summary = $this->summarize($summary, $maxLength);
        }
        if ($stripTags) {
            $description = strip_tags($description);
        }
        $variables = ['link' => '', 'title' => '', 'description' => '', 'date' => ''];
        foreach ($item as $element) {
            $elementName = $element->getName();
            if ($elementName == 'pubDate') {
                $elementName = 'date';
            }
            $elementValue = trim((string)$element ?? "");
            $variables[$elementName] = $elementValue;
        }
        $variables['summary'] = $summary;
        $variables['description'] = $description;

        //$variables = [
        //    'link' => (string)$item->link,
        //    'title' => (string)$item->title,
        //    'description' => $description,
        //    'summary' => $summary,
        //    'date' => isset($item->pubDate) ? (string)$item->pubDate : '',
        //];

        return $variables;
    }
}
