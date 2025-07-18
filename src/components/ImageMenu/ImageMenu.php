<?php

namespace Opensitez\Simplicity\Components;
use Opensitez\Simplicity\MSG;
class ImageMenu extends \Opensitez\Simplicity\Component
{
    public $name = "Image Gallery";
    public $description = "Implements an image gallery";
    public $app = [];
    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::onComponentLoad:
                // Register this component as a route type handler for redirects
                $this->framework->register_type('routetype', 'imagemenu');
                $this->framework->register_type('blocktype', 'imagemenu');
                break;
        }
        return parent::on_event($event);
    }
    public function render($app = [])
    {
        $current_site = $this->config_object->get('site');
        $debug = $this->framework->get_component('debug') ?? false;
        $paths = $this->config_object->get('paths');

        // echo $debug->printArray($current_site);

        $page = $this->framework->get_component("page");
        $i18n = $this->framework->get_component("i18n");
        $output = "\n";
        if (!$app) {
            $app = $this->config_object->get('current')??[];
        }
        $filter = $app['filter'] ?? "";

        $autofilter = $app['autofilter'] ?? false;

        if ($filter && !is_array($filter)) {
            $filter = [$filter];
        } else if (!$filter) {
            $filter = [];
        }
        if ($autofilter) {
            $path = $app['path'] ?? [];
            if ($path) {
                foreach (explode('+', $path) as $param) {
                    $filter[] = $param;
                }
            }
        }

        $skip = $app['skip'] ?? "";
        if ($skip && !is_array($skip)) {
            $skip = [$skip];
        }
        $scale = 1;
        if (isset($app['zoom'])) {
            if (!$app['zoom']) {
                $zoom = false;
            } else {
                $scale = $app['zoom'] ?? "1";
                $zoom = true;
            }
        } else {
            $zoom = true;
            $scale = 1;
        }
        if (isset($app['width']) && isset($this->config['current']['height'])) {
            $imgwidth = $app['width'] ?? "auto";
            $imgheight = $app['height'] ?? "150";
        } elseif (isset($this->config['current']['width'])) {
            $imgwidth = $app['height'] ?? "auto";
            $imgheight = $app['width'] ?? "150";
        } else {
            $imgwidth = $app['width'] ?? "auto";
            $imgheight = $app['height'] ?? "150";
        }
        $show = [
            'group-title' => true,
            'group-image' => false,
            'item-title' => true,
            'item-image' => true,
            'details' => true,
            'links' => true,
        ];
        $defimages = [
            "group-glyph" => "",
            "group-icon" => "",
            "group-image" => "",
            "item-glyph" => "",
            "item-icon" => "",
            "item-image" => "",
            "item-glyph" => "",
            "item-icon" => "",
            "link-image" => "",
            "link-glyph" => "",
            "link-icon" => "",
        ];
        $defstyles = [
            "item" => "",
            "item-image" => "",
            "item-title" => "",
            "item-hover" => "",
            "group-title" => "",
            "group-image" => "",
            "group" => "",
            "link" => "",
        ];
        foreach ($defstyles as $stylename => $stylevalue) {
            $defstyles[$stylename] = $app['style'][$stylename] ?? $stylevalue;
            if ($defstyles[$stylename] && $stylename != "item-hover") {
                $defstyles[$stylename] = " style='" . $defstyles[$stylename] . "' ";
            }
        }
        //print_r($defstyles);exit;
        foreach ($show as $idx => $value) {
            $show[$idx] = $app['show'][$idx] ?? $value;
        }
        $galleryid = $app['id'] ?? "";
        if (!is_string($galleryid) || !isset($current_site['definition']['data'][$galleryid])) {
            return "[invalid gallery id $galleryid]";
        }

        $galleryData = $current_site['definition']['data'][$galleryid] ?? [];

        // echo $debug->printArray($galleryData);

        foreach ($galleryData as $groupid => $group) {
            $groupid = str_replace(" ", "-", $groupid);
            if ($filter && !in_array($groupid, $filter)) {
                continue;
            }
            $before = $group['before'] ?? [];
            $after = $group['after'] ?? [];
            if ($skip && in_array($groupid, $skip)) {
                continue;
                //exit;
            }
            $groupname = $group['text'] ?? "";
            $groupurl = $group['url'] ?? "";
            $group_name = $i18n->get_i18n_value($groupname);
            $groupurl = $group['url'] ?? "";
            $groupurl = $i18n->get_i18n_value($groupurl);
            $groupimage = $i18n->get_i18n_value($group['image'] ?? $defimages['group-image']);
            $groupglyph = $group['glyph'] ?? $defimages['group-glyph'];
            $groupglyph = $group['icon'] ?? $defimages['group-icon'];
            $imagedir = $group['imagedir'] ?? "";
            if (is_array($imagedir)) {
                $imagedir = reset($imagedir);
            }
            $imagedir =  $this->replace_paths($imagedir);

            if ($show['group-image'] && ($group['image'] ?? "")) {
                //print "hi";exit;
                $groupimage = "<img class='gallery-image gallery-group-photo' width='$imgwidth' height='$imgheight'  src='$groupimage'>\n";
            } else {
                $groupimage = "";
            }
            $groupglyph = ($group['glyph'] ?? "") ? $group['glyph'] : $defimages['group-glyph'];
            $groupicon = ($group['icon'] ?? "") ? $group['icon'] : $defimages['group-icon'];
            $icontag = $groupicon ? "<img class='item-icon' src='$imagedir/$groupicon'>" : "";
            $glyphtag = $groupglyph ? "<i class='bi bi-$groupglyph'></i>" : "";
            if ($show['group-title'] && $groupname) {
                $output .= "<div id='$groupid'  " . $defstyles['group'] . " class='gallery-group'>" .
                    "\n<h2  " . $defstyles['group-title'] . " class='gallerygroup-title'>" .
                    "\n$glyphtag $icontag" . $groupimage . $group_name . "</h2>";
            } elseif ($show['group-title'] && $groupimage) {
                $output .= "<div id='gallery-$groupid' class='gallery-group'>\n<h3  " . $defstyles['group-title'] . "  class='gallerygroup-title'>$groupicon $icontag $glyphtag $groupimage</h3>\n";
            } else {
                $output .= "<div id='$groupid' class='gallery-group'>\n";
            }
            $section_object = $this->framework->get_component("section");
            $before_inserts = $section_object->render_section_contents($before, $app);
            $output .= "<div class='gallery-before'>\n" . $before_inserts . "\n</div>\n";
            $output .= "<div class='gallery-content'>";
            //exit;
            if ($group['values'] ?? false) {
                foreach ($group['values'] ?? [] as $childid => $child) {
                    $childid = str_replace(" ", "-", $childid);
                    if ($skip && in_array($childid, $skip)) {
                        continue;
                        //exit;
                    }
                    //print_r ($child); print "<pre></n>";
                    $childurl = $child['url'] ?? "";
                    $childurl = $i18n->get_i18n_value($childurl);
                    $childname = $child['text'] ?? "";
                    $childglyph = $child['glyph'] ?? "";
                    $childicon = $child['icon'] ?? "";
                    $childname = $i18n->get_i18n_value($childname);
                    //print_r($child);

                    $itemglyph = ($child['glyph'] ?? "") ? $child['glyph'] : $defimages['group-glyph'];
                    $itemicon = ($child['icon'] ?? "") ? $child['icon'] : $defimages['group-icon'];
                    $icontag = $itemicon ? "<img class='item-icon' " . $defstyles['image'] . " src='$imagedir/$itemicon'>\n" : "";
                    $glyphtag = $itemglyph ? "<i class='bi bi-$itemglyph'></i>" : "";
                    //print "<h1>||-- $glyphtag</h1>\n";
                    if ($childname) {
                        $alttext = 'alt ="' . htmlentities($childname) . '"';
                    }
                    $childimage = $i18n->get_i18n_value($child['image'] ?? "");


                    if ($show['item-image'] && ($child['image'] ?? false)) {
                        if ($childimage) {
                            $imgtag = "<img width='$imgwidth'   " . $defstyles['item-image'] . "   $alttext class='gallery-image gallery-photo'\n height='$imgheight' src='$imagedir/" . $childimage . "' >\n";
                            if ($childurl) {
                                $imgtag = "\n<a href='$childurl'>$imgtag</a>\n";
                            }
                        } else {
                            $imgtag = "";
                        }
                    } else {
                        $imgtag = "";
                    }
                    //print "++ $imgtag \n";
                    if ($show['item-title']) {
                        if ($childurl) {
                            $itemicon = $defimages['item-icon'] ? "<img class='item-icon' src='{$defimages['item-icon']}'>\n" : "";
                            $itemglyph = $defimages['item-glyph'] ? "<i class='bi bi-{$defimages['item-icon']}'></i>\n" : "";
                            $childh3 = "<div   " . $defstyles['item-title'] . "  class='gallery-item-title'>$glyphtag{$icontag} <a  " . $defstyles['item-title'] . " href='$childurl'>$childname</a></div>\n";
                        } else {
                            $childh3 = "<div   " . $defstyles['item-title'] . "  class='gallery-item-title'>$glyphtag{$icontag} $childname</div>\n";
                        }
                    } else {
                        $childh3 = "";
                    }
                    $output .= "<div " . $defstyles['item'] . " class='gallery-item' id='$childid'>\n$childh3\n$imgtag\n";
                    if ($show['details'] && ($child['fields'] ?? [])) {
                        $output .= "<ul 'class='gallery-ul'>";
                        foreach ($child['fields'] ?? [] as $datakey => $datavalue) {
                            if (is_array($datavalue)) {
                                $datavalue = $i18n->get_i18n_value($datavalue);
                            }
                            if ($datavalue) {
                                $output .= "<li><b>$datakey:</b> $datavalue</li>\n";
                            }
                        }
                        $output .= "</ul>";
                    }
                    if ($show['links'] && ($child['values'] ?? [])) {
                        $output .= "<ul class='gallery-links'>";
                        foreach ($child['values'] ?? [] as $linkid => $link) {
                            $linkid = str_replace(" ", "-", $linkid);

                            $linkurl = $link['url'] ?? "";
                            $linkurl = $i18n->get_i18n_value($linkurl);
                            $linktext = $link['text'] ?? "";
                            $linktext = $i18n->get_i18n_value($linktext);
                            $linkglyph = ($link['glyph'] ?? "") ? $link['glyph'] : $defimages['group-glyph'];
                            $linkicon = ($link['icon'] ?? "") ? $link['icon'] : $defimages['group-icon'];

                            $icontag = $linkicon ? "<img class='item-icon' src='$imagedir/$linkicon'>\n" : "";
                            $glyphtag = $linkglyph ? "<i class='bi bi-$linkglyph'></i>\n" : "";

                            //print "$linktext\n";
                            if ($linkurl) {
                                $output .= "<li class='gallery-li-link'><a   " . $defstyles['link'] . "  href='$linkurl'>$icontag$glyphtag$linktext</a></li>\n";
                            }
                        }
                        $output .= "</ul>";
                    }
                    $output .= "</div>"; //end child div
                }
            }
            $after_inserts = $section_object->render_section_contents($after, $app);
            $output .= "<div class='gallery-after'>\n" . $after_inserts . "\n</div>\n";

            $output .= "</div>\n";
            $output .= "</div>\n";
        }
        //print $galleryid;
        //exit;
        $output .= "<style>

        \n</style>";
        //print $output;
        //exit;
        return $output;
    }
    public function on_render_page($app = [])
    {
        if ($app)
            $this->app = $app;
        else {
            $app = $this->app;
        }
        return $this->render($app);
    }
}
