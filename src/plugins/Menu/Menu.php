<?php

namespace Opensitez\Simplicity\Plugins;
use Opensitez\Simplicity\MSG;
class Menu extends \Opensitez\Simplicity\Plugin
{
    public $name = "Menu";
    public $description = "Renders menus and navigation bars";
    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::onComponentLoad:
                // Register this plugin as a content provider for WordPress
                $this->framework->register_type('blocktype', 'menu');

                break;
        }
        return parent::on_event($event);
    }    
    function make_menu($menus, $params = [])
    {

        $i18n = $this->framework->get_component("i18n");
        $paths = $this->config_object->get('paths');
        $defaults = [
            "activeclass" => "",
            "activeid" => "",
            "default" => "",
            "linkclass" => "nav-link",
            "menuclass" => "nav-item",
        ];
        $filter = $params['filter'] ?? false;
        if ($filter && !is_array($filter)) {
            $filter = [$filter];
        }
        $skip = $params['skip'] ?? "";
        if ($skip && !is_array($skip)) {
            $skip = [$skip];
        }
        $activeclass = $params["activeclass"] ?? $defaults['activeclass'];
        $activeid = $params['activeid'] ?? $defaults["activeid"];
        $menuclass = $params['menuclass'] ?? $defaults['menuclass'];
        $linkclass = $params['linkclass'] ?? $defaults['linkclass'];
        $nav = "";
        $nav .= "<style> .menu-img {max-height:20px;width:auto}</style>\n";
        foreach ($menus as $navid => $navblock) {
            if ($filter && !in_array($navid, $filter)) {
                continue;
            }
            // print_r($navblock);exit;
            if ($skip && in_array($navid, $skip)) {
                continue;
                //exit;
            }
            $navz = '
           <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item active">
                        <a class="nav-link" href="#">Home</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown1" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            About
                        </a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdown1">
                            <a class="dropdown-item" href="#">About Us</a>
                            <a class="dropdown-item" href="#">Our Team</a>
                            <a class="dropdown-item" href="#">Mission & Vision</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown2" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Blog
                        </a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdown2">
                            <a class="dropdown-item" href="#">Latest Posts</a>
                            <a class="dropdown-item" href="#">Popular Posts</a>
                            <a class="dropdown-item" href="#">Categories</a>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Contact</a>
                    </li>
                </ul>
            </div>
               ';
            $menutype = $navblock['type'] ?? "menu";
            if ($menutype == "vars") {
                $nav .= "<div id=$navid>" . ($this->config['vars'][$navblock["name"]] ?? "") . "</div>";
            }
            if ($menutype == "block") {
                $nav .= "<div id=$navid>" . ($this->config['blocks'][$navblock["name"]] ?? "") . "</div>";
            } elseif ($menutype == "menu") {
                $nav .= "    <div id='$navid'>\n        <ul class='navbar-nav ml-auto'>\n";
                $imagedir = $navblock['imagedir'] ?? "";
                $imagedir = '/' . ($paths['sitepath'] ?? "") . '/' . $imagedir;
                $defimages = [
                    'menu-glyph' => '',
                    'menu-icon' => '',
                    'child-glyph' => '',
                    'child-icon' => '',
                    'basedir' => $imagedir
                ];

                foreach ($navblock['values'] ?? [] as $menuid => $menu) {
                    $menuid = str_replace(" ", "-", $menuid);

                    if ($filter && !in_array($menuid, $filter)) {
                        continue;
                    }
                    if ($skip && in_array($menuid, $skip)) {
                        continue;
                    }

                    $children = $menu["values"] ?? [];
                    $menutext = $menu['text'] ?? "";
                    $menutext = $i18n->get_i18n_value($menutext);
                    $menuurl = $menu['url'] ?? '#';
                    $menuurl = $i18n->get_i18n_value($menuurl);

                    $menuglyph = ($menu['glyph'] ?? "") ? $menu['glyph'] : $defimages['menu-glyph'];
                    $menuicon = ($menu['icon'] ?? "") ? $menu['icon'] : $defimages['menu-icon'];
                    $icontag = $menuicon ? "<img class='menu-icon menu-img' src='$imagedir/$menuicon'>" : "";
                    $glyphtag = $menuglyph ? "<i class='bi menu-glyph menu-img bi-$menuglyph'></i>" : "";

                    if (empty($children)) {
                        $nav .= "            <li id='$menuid' class='menu-entry $menuclass'>$glyphtag$icontag<a class='$linkclass' href='$menuurl'> $menutext</a></li>\n";
                    } else {
                        $nav .= "       <li id='$menuid' class='$menuclass dropdown'> \n";
                        $nav .= "           <a class='$linkclass dropdown-toggle' href='$menuurl' data-bs-toggle='dropdown' \n" .
                            '                            role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"' .
                            "> \n" .
                            "                            $glyphtag$icontag $menutext  </a>\n";
                        $nav .= "           <ul class='dropdown-menu'>\n";

                        foreach ($children as $childid => $child) {
                            $childid = str_replace(" ", "-", $childid);

                            if ($filter && !in_array($childid, $filter)) {
                                continue;
                            }
                            if ($skip && in_array($childid, $skip)) {
                                continue;
                            }
                            $childurl = $child["url"] ?? "#";
                            $childurl = $i18n->get_i18n_value($childurl);
                            $childglyph = ($child['glyph'] ?? "") ? $child['glyph'] : $defimages['child-glyph'];
                            $childicon = ($child['icon'] ?? "") ? $child['icon'] : $defimages['child-icon'];
                            $icontag = $childicon ? "<img class='menu-icon menu-img' src='$imagedir/$childicon'>" : "";
                            $glyphtag = $menuglyph ? "<i class='bi menu-glyph menu-img bi-$childglyph'></i>" : "";

                            $childtext = $child["text"] ?? "";
                            $childtext = $i18n->get_i18n_value($childtext);
                            if (is_array($childtext)) {
                                $childtfpt = reset($childtext);
                            }
                            $nav .= "                 <li id='$childid'><a class='dropdown-item' href='$childurl'>$glyphtag$icontag $childtext </a></li>\n";
                        }
                        $nav .= "            </ul>\n";
                        $nav .= "       </li>\n";
                    }
                }
                $nav .= "       </ul>\n    </div>\n";
            }
        }

        return $nav;
    }
}
