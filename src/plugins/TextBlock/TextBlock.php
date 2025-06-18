<?php

namespace Opensitez\Simplicity\Plugins;

use Opensitez\Simplicity\MSG;

use \Parsedown;
use \QRCode;

class TextBlock extends Block
{
    function render_insert_text($text, $options = [])
    {
        $default = [
            'encoding' => 'utf-8',
            'content-type' => 'html'
        ];

        $class = $options['class'] ?? "";
        $style = $options['style'] ?? "";

        $encoding = $options['encoding'] ?? $default['encoding'];
        $content_type = $options['content-type'] ?? $default['content-type'];
        if (is_array($text)) {
            $text = implode("\n", $text);
        }

        switch ($content_type) {
            case "slideshow":
                $slidestyle = addslashes($app['style'] ?? "");
                $carouselclass = $app['carousel-class'] ?? [];
                $datainterval = $app['interval'] ?? "";
                if ($datainterval) {
                    $datainterval = " data-interval=\"$datainterval\" ";
                } else {
                    $datainterval = "";
                }
                if ($style) {
                    $slidestyle = " style=\"$slidestyle\" ";
                } else {
                    $slidestyle = "";
                }
                if (!is_array($carouselclass))
                    $carouselclass = [$carouselclass];
                if ($carouselclass) {
                    $carouselclass = implode(" ", $carouselclass);
                } else {
                    $carouselclass = "";
                }

                $parsed_content = '<div ' . $slidestyle . $datainterval . '" class="carousel slide ' . $carouselclass . '" data-bs-ride="carousel">';
                $parsed_content .= '<div class="carousel-inner">';
                $start = $options['start'] ?? 0;
                $idx = 0;
                if (!is_array($text)) {
                    $text = explode("\n", $text);
                }
                foreach ($text as $line) {
                    if ($line) {
                        if ($start == $idx)
                            $active = "active";
                        else
                            $active = "";
                        $parsed_content .= "<div class='carousel-item $active'>
                            $line\n</div>";
                    }
                    $idx += 1;
                }
                $parsed_content .= "\n</div>\n</div>";
                break;

            case "qrcode":
                $qr = new Qrcode(null);
                $parsed_content = $qr->render_page();
                break;
            case "rainbow-text":
                $parsed_content = $this->rainbow_text($text, $options);
                break;
            case "markdown":
                $Parsedown = new Parsedown();
                $parsed_content = $Parsedown->text($text);
                break;
            case "nl2br":
                $parsed_content = nl2br($text);
                break;
            case "text":
                $parsed_content = nl2br(htmlentities($text));
                break;
            case "html":
                $parsed_content = $text;
                break;
            default:
                $parsed_content = $text;
        }

        return $parsed_content;
    }

    function rainbow_text($input, $options)
    {
        $retval = "";
        $content_type = $options['content-type'] ?? "html";
        $encoding = $options['encoding'] ?? "utf-8";
        $style = $options['style'] ?? "font-family:monospace;";
        $colors = $options['colors'] ?? [
            ["bg" => "#000000", "fg" => "#ffffff"],
            ["bg" => "#AA0000", "fg" => "#ffffff"],
            ["bg" => "#FF0000", "fg" => "#ffffff"],
            ["bg" => "#DF7401", "fg" => "#ffffff"],
            ["bg" => "#deb887", "fg" => "0"],
            ["bg" => "#6495ed", "fg" => "#ffffff"],
            ["bg" => "#088A85", "fg" => "#ffffff"],
            ["bg" => "#4B088A", "fg" => "#ffffff"],
            ["bg" => "#006900", "fg" => "#FFFFFF"],
            ["bg" => "#04B404", "fg" => "#FFFFFF"],
            ["bg" => "#77FF77", "fg" => "#0"]
        ];
        $color = 1;
        $colorcount = count($colors);
        $lines = explode("\n", $input);
        $num_lines = count($lines);
        $retval .= "<style>\n";
        $retval .= " .rainbowline {font-family: monospace} ";
        foreach ($colors as $idx => $color) {
            $bg = $color['bg'] ?? "black";
            $fg = $color['fg'] ?? "white";
            $retval .= "  .color$idx {background-color: $bg; color:$fg; display: inline-block; font-weight: bold; min-width: 15px;text-align:center;font-family: monospace!important}\n";
        }
        $retval .= ".rainbowline {$style}\n";
        $retval .= "</style>";

        foreach ($lines as $line) {
            $color = 2;
            $line = trim($line);
            while (strstr($line, "(")) {
                if ($color == $colorcount)
                    $color = 0;
                $textstyle = "<span class='color$color'>";
                $line = str_replace_once("(", $textstyle, $line);
                $line = str_replace_once(")", "</span>", $line);
                $color++;
            }
            $retval .= "\n<div class=rainbowline>$line&nbsp;</div>";
        }
        $retval = htmlspecialchars_decode(htmlentities($retval, ENT_NOQUOTES, $encoding, FALSE));
        return ($retval);
    }
}
