<?php

namespace Opensitez\Simplicity\Plugins;


function validate_folder_path($path, $maxdepth = 3, $maxpartlength = 50, $options = [])
{

    $maxpartlength = $options['maxparthlength'] ?? 50;
    $default = [
        'matchstring' => "/^[a-zA-Z0-9][a-zA-Z0-9\'\ \+\.\(\)\-\_]{0,$maxpartlength}$/",
        'maxdepth' => 3,
    ];
    $maxdepth = $options['maxdepth'] ?? $default['maxdepth'];
    $matchstring = $matchstring ?? $default['matchstring'];
    //print $matchstring;
    $path = trim($path, "/");
    if (!$path)
        $path = "";
    $idx = 0;
    foreach (explode("/", $path) as $part) {
        $idx += 1;
        if (!preg_match($matchstring, $part)) {
            $path = "";
        }
    }
    if ($idx > $maxdepth) {
        $path = "";
    }

    return $path;
}

class Folder extends \Opensitez\Simplicity\Plugin
{
    public $name = "Folders";
    public $description = "Allows to serve a bunch of files";
    var $params = array();
    function get_menus($app = [])
    {
        $menus = [
            "content" => [
                "text" => "Virtual Folders",
                "weight" => -2,
                "image" => "genimgfolder1.png",
                "children" => [
                    "folder" => ["plugin" => "folder", "page" => "default", "text" => "Virtual Folders", "category" => "all"],
                ],
            ],

        ];
        return $menus;
    }

    public function list_dir($prefix, $basedir, $app_path, $options)
    {
        $block_plugin = $this->plugins->get_plugin('block');
        $current_site = $this->config_object->getCurrentSite();
        $paths = $this->config_object->getPaths();

        $retval = "";
        $full_path = "$basedir/$app_path";
        $i18n = $this->plugins->get_plugin("i18n");
        $defaults = [
            "allowindex" => "yes",
            "capitalize" => "yes",
            "indexfiles" => "index.html",
            "extensions" => [],
            "replacements" => ["webpath"=>"$app_path"],
            "hide" => ["index.html", "index.php", ".htaccess"]
        ];

        $indexfiles = $options['indexfiles'] ?? $defaults["indexfiles"];
        $allowindex = $options['allowindex'] ?? $defaults['allowindex'];
        $hide = $options['hide'] ?? $defaults['hide'];
        $capitalize = $options["capitalize"] ?? "yes";
        $replacements = $options['replacements'] ?? $defaults['replacements'];
        $content_type = $options['content-type'] ?? "html";

        if (!is_array($indexfiles)) {
            $indexfiles = [$indexfiles];
        }

        $flist = scandir($full_path, SCANDIR_SORT_ASCENDING);
        $retval .= "<style>
            .file-list ul {list-style-type: none;display:inline-block;}
            .file-list li {width: 45%; display: inline-block;vertical-align: top}
            \n</style>";

        $retval .= "<div class='file-list'><ul class='files list-group'>\n";
        if ($indexfiles) {
            $vararrays = [$current_site['vars'], $paths];
            foreach ($indexfiles as $indexfile) {
                $indexpath = "$full_path/$indexfile";
                if (file_exists($indexpath)) {
                    $found = false;
                    foreach ($i18n->accepted_langs() as $lang => $lang_details) {
                        if ((ctype_alpha($lang) && strlen($lang) == 2) && is_file($indexpath . ".$lang")) {
                            $fcontent = @file_get_contents($indexpath . ".$lang");
                            $found = true;
                        }
                    }
                    if (!$found) {
                        $fcontent = file_get_contents($indexpath);
                    }
                    $options['content-type'] = $content_type;
                    $retval .= $this->substitute_vars($block_plugin->render_insert_text($fcontent, $options), $vararrays);
                }
            }
        }
        //exit;

        $folderglyph = $options['folder-glyph'] ?? "folder";
        $fileglyph = $options['file-glyph'] ?? "file";
        $foldertag = $folderglyph ? "<i class='bi bi-$folderglyph'></i>" : "";
        $filetag = $fileglyph ? "<i class='bi bi-$fileglyph'></i>" : "";

        if ($allowindex) {
            foreach ($flist as $fname) {
                $fname = trim($fname, "/");
                if (validate_folder_path($fname) && (!in_array($fname, $hide))) {
                    $parsed_name = $replacements[$fname] ?? $fname;
                    $parsed_name = preg_replace("/[-\ ]/", " ", $parsed_name);
                    //$parsed_name= str_replace("-"," ",$parsed_name);
                    $parsed_fname = pathinfo($parsed_name)['filename'];
                    if ($capitalize == "yes") {
                        $parsed_fname = ucfirst($parsed_fname);
                    }
                    $full_file_path = $full_path . "/$fname";
                    //print_r($parsed_fname);exit;
                    $final_path = [];
                    if ($prefix)
                        $final_path[] = trim($prefix, "/");
                    if ($app_path)
                        $final_path[] = rtrim($app_path, "/");
                    if ($fname)
                        $final_path[] = urlencode(rtrim($fname, "/"));
                    //print $full_file_path . "\n";
                    if (is_dir($full_file_path)) {
                        $retval .= "    <li class='list-group-item'><a href='/" . implode("/", $final_path) . "'>$foldertag $parsed_fname</a></li>\n";
                    } else {
                        $retval .= "    <li class='list-group-item'><a href='/" . implode("/", $final_path) . "'>$filetag $parsed_fname</a></li>\n";
                    }
                    //$retval .= "<li><a href='$app_path/$fname'>$prefix / $app_path / $fname </a></li>"; 
                }
            }
            $retval .= "</ul></div>\n\n";
        }
        return $retval;
        //print_r($flist);exit;
    }
    public function on_render_page($app)
    {
        $block_plugin = $this->plugins->get_plugin('block');
        $debug = "";
        $validpath = false;
        $current_site = $this->config_object->getCurrentSite();
        $paths = $this->config_object->getPaths();
        $basedir = $paths['datafolder'] . "/" . trim($app['basedir'] ?? "", "/");
        if (!validate_folder_path($app['basedir'] ?? "")) {
            return "invalid Base Dir";
        }
        $replacements = $app['replacements'] ?? [];
        $extensions = $app['extensions'] ?? [];
        if (!is_array($extensions)) {
            $extensions = [$extensions];
        }
        $app_path = validate_folder_path(urldecode($app['path'] ?? ""));
        $full_path = "$basedir/$app_path";
        $allowindex = $app['allowindex'] ?? "yes";
        $extensions = $app['extensions'] ?? "yes";
        $folderglyph = $app['folder-glyph'] ?? "folder";
        $fileglyph = $app['file-glyph'] ?? "body-text";
        $foldertag = $folderglyph ? "<i class='bi bi-$folderglyph'></i>" : "";
        $filetag = $fileglyph ? "<i class='bi bi-$fileglyph'></i>" : "";
        $content_type = $app['content-type'] ?? "html";
        $indexfiles = $app['indexfiles'] ?? "index.html";

        if (is_dir($full_path)) {
            $prefix = "/" . $app['route'] ?? "";
            $options = [
                "replacements" => $replacements,
                "extensions" => $extensions,
                "allowindex" => $allowindex,
                "indexfiles" => $indexfiles,
                'content-type' => $content_type,
                'folder-glyph' => $folderglyph,
                'file-glyph' => $fileglyph
            ];
            return $this->list_dir($prefix, $basedir, $app_path, $options);
        } else {
            //$fname = $basedir . "/" . $path;
            //print $full_path;exit;
            $i18n = $this->plugins->get_plugin("i18n");

            $found = false;
            foreach ($i18n->accepted_langs() as $lang => $lang_details) {
                if ((ctype_alpha($lang) && strlen($lang) == 2) && is_file($full_path . ".$lang")) {
                    $fcontent = @file_get_contents($full_path . ".$lang");
                    $found = true;
                }
            }
            if (!$found) {
                $fcontent = @file_get_contents($full_path);
            }
            $encoding = $current_site['charset'] ?? "utf-8";
            $options = [
                'encoding' => $encoding,
                'content-type' => $content_type
            ];
            $rendered_content = $block_plugin->render_insert_text($fcontent, $options, $app);

            if ($fcontent && $content_type == "rainbow-text") {
                $rendered_content = "<h1>" . $app['titleprefix'] . " "
                    . pathinfo($app_path)['filename'] . "</h1>" . $rendered_content;
            }

            return $rendered_content;
        }
    }
}
