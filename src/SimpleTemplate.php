<?php

namespace Opensitez\Simplicity;
/*
    $content="hello world of themes";
    $theme="bootstrap";
    $mytemplate = new SimpleTemplate("themes/$theme/main.php");
    $mytemplate -> setVars( ["var1"=>"value1","var2"=>"value2",'content'=>$content]);
    $mytemplate -> render();
*/

class SimpleTemplate extends \Opensitez\Simplicity\Base
{
    private $FileName;
    private $contents;
    private $parsed;
    private $vars = [];
    var $leftdelim = "{{";
    var $rightdelim = "}}";

    function __construct($templateName = "")
    {
        $this->setFile($templateName);
    }
    /* function setFile - sets the template file and loads it */
    function setFile($templateName)
    {
        $this->parsed = "";
        if (file_exists($templateName)) {
            $this->FileName = $templateName;
            $this->contents = file_get_contents($templateName);
        }
    }
    public function setLeftDelim($leftdelim)
    {
        $this->leftdelim = $leftdelim;
    }
    public function setRightDelim($rightdelim)
    {
        $this->rightdelim = $rightdelim;
    }
    public function assign($var, $value)
    {
        $this->vars[$var] = $value;
    }
    /* function setVars - this will set variable from an array key->value */
    function setVars($templatevars)
    {
        //do this to append values
        foreach ($templatevars as $key => $value) {
            $this->vars[$key] = $value;
        }
    }
    /* function makeMenu - this will generate ul/li menu found in typical templates like bootstrap */
    function makeMenu($menus, $params = [], $defaultvalue = 0)
    {
        $ulmenu = "";
        $menuclass = $params['menu'] ?? "navbar-nav";
        $entryclass = $params['entry'] ?? "menu-entry nav-item";
        $entryactive = $params['menuactive'] ?? "active";
        $linkclass = $params['link'] ?? "nav-link";

        if (isset($menuclass))
            $classtext = " class='" . $entryclass . "'";
        else
            $classtext = "";
        if (isset($menuactive))
            $activetext = " class='" . $entryactive . "'";
        else
            $activetext = "";
        $i = 0;
        foreach ($menus as $key => $value) {
            $i++;
            if ($i == $defaultvalue) {
                $ulmenu .= "	<li $activetext id='nav-$key'><a class='$linkclass' href='$value'><span>$key</span></a></li>\n";
            } else {
                $ulmenu .= "	<li $classtext id='nav-$key'><a href='$value'><span>$key</span></a></li>\n";
            }
        }
        return $ulmenu;
    }
    /* generic method where you pass a template string, variables to substitute and delimeters
     * this is so that you can parse multiple sub templates without having multiple class instances 
     */
    // public function substitute_vars($contents, $vars, $blocks = [])
    // {
        
    //     if (!$leftdelim)
    //         $leftdelim = $this->leftdelim;
    //     if (!$rightdelim)
    //         $rightdelim = $this->rightdelim;

    //     $keys = array_map(fn ($key) => $leftdelim . $key . $rightdelim, array_keys($vars));
    //     $values = array_values($vars);
    //     foreach ($values as $key => $value) {
    //         if (is_array($value)) {
    //             $values[$key] = "<<array>>";
    //         }
    //     }

    //     if ($keys) {
    //       $contents = str_replace($keys, $values??[], $contents??"");
    //     }
    //     return $contents;
    // }
    /* function to render a template pass*/
    function render($contents = "", $vars = false)
    {
        if (!$vars) {
            $vars = $this->vars;
        }
        if (!$contents) {
            $contents = $this->contents;
        }
        $retval = $contents;

        //hardcode compatibility for smarty which has more than variables	
        $templatedefaults = [
            'nocache' => '',
            '/nocache' => '',
        ];
        $retval = $this->substitute_vars($retval, $templatedefaults, "{{", "}}");
        $this->parsed = $this->substitute_vars($retval, $vars);
        return $this->parsed;
    }
}
