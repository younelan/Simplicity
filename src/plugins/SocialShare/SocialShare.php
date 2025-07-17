<?php

namespace Opensitez\Simplicity\Plugins;

use Opensitez\Simplicity\MSG;

class SocialShare extends \Opensitez\Simplicity\Plugin
{
    public $name = "Social Share Button";
    public $description = "Gets Social media share";

    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::onComponentLoad:
                $this->framework->register_type('blocktype', 'socialshare');
                break;
        }
        return parent::on_event($event);
    }

    public function facebook_share($url, $text = "")
    {
        if (!$text)
            $text = "Share on Facebook";
        $retval =
            "<a href='https://www.facebook.com/sharer/sharer.php?u=$url' target='_blank'>\n" .
            "<img src='/images/fbshare.png' alt='Share on Facebook'></a>\n";
        return $retval;
    }
    public function email_share($url, $text = "")
    {
        if (!$text)
            $text = "Share Via Email";
        $retval =
            "<a href='mailto:?subject=$text&amp;body=$text' title='$text'><img src='/images/emailshare.png'></a>\n";
        return $retval;
    }
    public function linkedin_share($url, $text = "")
    {
        if (!$text)
            $text = "Share Via LinkedIn";
        $retval =
            "<a href='https://www.linkedin.com/shareArticle?mini=true&url=$url&title=$text'>\n
            <img src='/images/linkedinshare.png' alt='Share on LinkedIn'></a>\n";
        return $retval;
    }
    public function whatsapp_share($url, $text = "")
    {
        if (!$text)
            $text = "Share Via Whatsapp";

        $retval =
            "<a href='https://wa.me/?text=$text%20$url' target='_blank'>\n" .
            "<img src='/images/whatsappshare.png' alt='Share on WhatsApp'></a>\n";
        return $retval;
    }
    public function twitter_share($url, $text = "Share on Twitter")
    {
        $retval =
            "<a href='https://twitter.com/intent/tweet?text=$text&url=$url' target='_blank'>\n" .
            "<img src='/images/twittershare.png' alt='Share on Twitter'></a>";
    }
    public function render_page($app)
    {
        $width = ($app['width'] ?? '50') . "px";

        $height = ($app['height'] ?? "50") . "px";
        $output = "";
        $output .= "<style>\n.social-share-item {display: inline-block}\n" .
            ".social-share-item img {width:$width;height:$height;}\n" .
            "</style>\n<div class='social-share'>\n";

        $server = $this->config_object->getServerVars();

        $domain = $server['HTTP_HOST'];
        $routestr = $app['route'] ?? "";
        if ($routestr == "default")
            $routestr = "";

        $url = urlencode($app['url'] ?? "");
        $text = urlencode($app['text'] ?? "");
        $scheme = $_SERVER['REQUEST_SCHEME'];
        $port = $_SERVER['SERVER_PORT'];
        if (!$url) {
            if ($port == 80 && $scheme == "http") {
                $url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            } elseif ($port == 80 && $scheme == "http") {
                $url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            } else {
                $url = $scheme . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            }
        }
        $url = urlencode($url);

        $output .= "\n<div class='social-share-item'>" . $this->facebook_share($url, $text) . "</div>\n";
        $output .= "<div class='social-share-item'>" . $this->whatsapp_share($url, $text) . "</div>\n";
        $output .= "<div class='social-share-item'>" . $this->twitter_share($url, $text) . "</div>\n";
        $output .= "<div class='social-share-item'>" . $this->linkedin_share($url, $text) . "</div>\n";
        $output .= "<div class='social-share-item'>" . $this->email_share($url, $text) . "</div>\n";
        $output .= "</div>";

        return $output;
    }
    public function on_render_page($app)
    {
        $output = $this->render_page($app);
        return $output;
    }
}
