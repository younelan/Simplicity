<?php

//************************************************
//templates helps (c) Youness El Andaloussi/ All rights reserved with block loading parts taken from the web
//
//This script is a basic template helper class which helps theme web pages... think of tinybutstrong... but much simpler
//Obviously, if complex generation is needed with caching & performance, use smarty or tinybutstrong instead.
//
//this is used in conjunction with poetrysitegenerator, a script that takes a collection of plain text acrostic poems and creates from them jpeg images with richtext
//using a pre-designed template to display the text within a web site with index and navigation
//the text in parenthesis will be highlighted in color while the regular words are in black
//the final web site can be seen at http://poetry.elandaloussi.net/ ... the acrostic crosswords is the page which shows this best
//*************************************************


/*    $templatevars=array("bodyclass","content2","pref_site","file","nav_bar","pref_intro","slideshowscript","bodytags","cat_details",
			   "pref_lang","pref_copyright","pref_greeting","themeheader","themefooter","sidecontent",
			   "nav_header","page_footer","section","nav_footer","content","themepath","ulmenu","feedbackintro",
			   "menu_home","menu_search","menu_contact","head_name","head_send","head_cancel","categoryname",
			   "head_subject","head_email","head_contactform","head_request","contactintro","sent_email","sent_name",
			   "sent_request","sent_subject","pref_email","emailcontent");
*/
/*
$content="hello world of themes. insert {content} here";
$theme name
$theme="blue_rain";
#web path for the theme
$themepath="/mza3za3itude/styles/$theme";

$mytemplate = new YTemplate("styles/$theme/main.php");
$mytemplate -> setFromGlobals( array("content","site_scripts",	"content2","site_copyright","categoryname","sidecontent","themepath","site_name","site_section","site_subsection","ulmenu"));
$mytemplate -> parse();

*/
class YTemplate
{
  var $FileName;
  var $Contents;
  var $Parsed;
  var $templatevars;
  var $templatevalues;
  var $blocks;
  var $blockvalues;
  
  var $t;

  // constructor, this will allow to set a template file name in the constructor
  function YTemplate($templateName="")
  {
    $this->setFile($templateName);  
  }
  //this allows to set the file name, generally called from the constructor
  function setFile($templateName)
  {
    $this->parsed = "";
    if(file_exists($templateName))
    {
	  $this->FileName=$templateName;
	  $this->Contents=file_get_contents($templateName);
	 // $this->_initTemplate();
	}
    
  }
  //this is the old load blocks function
  function loadblocks($blocks)
  {
     foreach($blocks as $key => $value)
	 {
	    if(file_exists( $this -> templatevalues ['{themepath}'] . "/$key.php"))
	    //print $key;
		$this->blocks[$key]=file_get_contents($this -> templatevalues ['{themepath}'] . "/$key.php"); 
		$this->blockvalues[$key]=$value;
	 }  
//print_r($this->blockvalues);
  }
  /* shamelessly ripped off from alvaro template by , LGPL 
     this code matches all blocks and turns them into variable */
  function _initTemplate()
  { 
	    preg_match_all("/\#BLOCK\s+(.*)?\s+\#\s*\n*\s*(.*)\s*\n*\s*\#\s+END\s+(\\1)\s+/ms",$this->t,$ma);
	    for ($i = 0; $i < count($ma[0]); $i++)
	    {
		$search = "/\s*\n*<!--\s+BLOCK\s+(" . $ma[1][$i] . ")?\s+-->(.*)<!--\s+END\s+(" . $ma[1][$i]. ")\s+-->\s*\n*/ms";
		print($search . "<hr>");
		exit;
		$replace = $this->delimiterStart . $ma[1][$i] . $this->delimiterEnd;
		$this->bl[$ma[1][$i]] =& new Template();
		$this->bl[$ma[1][$i]]->loadTemplateContent($ma[2][$i]);
		$this->t = preg_replace($search,$replace,$this->t);
	    }    
//		print_r($ma);
	//	exit;
  }
  
  //this will take a list of variable names in an array and load their values into variables to be later used in a template
  function setFromGlobals($templatevars)
  {
for($i=0;$i<count($templatevars);$i++)
{
  global $$templatevars[$i];
 if (!isset($$templatevars[$i])) $$templatevars[$i]="";
 $keyname = "{" . $templatevars[$i] . "}";
 $this -> templatekeys[$keyname]= $keyname ;
 $this -> templatevalues[$keyname]=stripslashes($$templatevars[$i]);
}
    
  }
  function setFromArray($templatevars)
  {
    foreach($templatevars as $key=>$value)
    {
      $key="{" . $key . "}";
	  $this -> templatekeys[$key]=$key;
	  $this -> templatevalues[$key]="$value";
	  //print "{$key}";
	  //print_r($this -> templatekeys);
	}
  }
  
  //this function is not strictly a template function but it allows to generate <ul></ul> menus  that can be styled simply within a template so that the menu can be integrated as a template var.
  function makeMenu($menus,$defaultvalue=0)
  {
    $ulmenu="";
	global $menuclass,$menuactive;
	if(isset($menuclass))
	 	$classtext=" class='" . $menuclass . "'";
	else
		$classtext="";
	if(isset($menuactive))
	 	$activetext=" class='" . $menuactive . "'";
	else
		$activetext="";
    $i=0;
    foreach($menus as $key=>$value	)
    {
      $i++;
	  if($i==$defaultvalue)
		  $ulmenu.="	<li $activetext id='nav-$key'><a href='$value'><span>$key</span></a></li>\n";
	  else
		  $ulmenu.="	<li $classtext id='nav-$key'><a href='$value'><span>$key</span></a></li>\n";
	}
    return $ulmenu;
  }
  
  //this allows to parse a template and return a generated page
  function parse()
  {

/*$emailcontent=str_replace($templatekeys,$templatevalues,$emailcontent);
$content=str_replace($templatekeys,$templatevalues,$content);
$template=str_replace($templatekeys,$templatevalues,$template);
  */
  if(is_array($this->blocks))
  foreach($this->blocks as $blockkey =>$blockcontent)
  {
    //$this->templatevalues["{" . $blockkey . "}"] = $blockcontent;
   // print "--- $blockcontent";
    $this->templatevars["{" . $blockkey . "}"] = "{" . $blockkey . "}";
    
    foreach($this->blockvalues[$blockkey] as $rowkey => $rowvalue )
    {
//print_r($value2);
     $tmpblock=$blockcontent;
     foreach($rowvalue as $key2 => $value2 )
     { // print "str_replace $key2 as $value2 <br>";
         $tmpblock=str_replace("{" . $key2 . "}",$value2,$tmpblock);
     //   foreach($this->blockvalues[$blockkey] as $rowkey => $rowvalue )
      }
      //print $tmpblockcontent;
      $this->templatevalues["{" . $blockkey . "}"] .= $tmpblock;
    }
    $this->templatekeys["{" . $blockkey . "}"] =  "{" . $blockkey . "}";
  }
  $this->parsed =   str_replace($this -> templatekeys,$this -> templatevalues,$this -> Contents);

  $this->parsed =   str_replace($this -> templatekeys,$this -> templatevalues,$this -> Contents);
  return $this->parsed;
}
}
?>
