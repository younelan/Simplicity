<?php
/* form helper (c) YEA 2006 / GPL
   version 0.01 - form gen
   version 0.02 - form processing for text
   version 0.03:
   		_ setpassword in form gen & form processing
		_ set maximum birthyear to 2000 for younger people
		_ form_gen & form_process are silent by default to integrate with templates
		_ changed the return of form_process to be array of success+content + errors
		_ added an email type (gen=text, proc= automatic regular expressions \n detection
   */

if(!isset($lstmonths))
	$lstmonths = array("1" => "January", "2" => "February", "3" => "March", "4" => "April", "5" => "May", "6" => "June", "7" => "July", "8" => "August", "9" => "September", "10" => "October", "11" => "November", "12" => "December");

function form_process($FormVars,$PostedValues,$formname)
{
  $ErrorValues="";
  //make sure myPostedValue is set and empty by default
  $MyPostedValue="";
  
  //iterate through all form values
  foreach($FormVars as $key => $value)
  {
    switch($value["type"])
    {
	  case "setpassword":
		
		//first check if passwords match
		if(!isset($PostedValues["$key"]) || !isset($PostedValues[$key . "passcheck"]) )
		{
			$ErrorValues[$key] ="Form tampered. field $key not provided";
		}
		else
		{
			if($PostedValues[$key] <> $PostedValues[$key . "passcheck"] )
			{
				if(isset($value["errnomatch"]))
					$ErrorValues[$key]=$value["errnomatch"];
				else
					$ErrorValues[$key]="the passwords don't match";
				$MyPostedValues[$key]="";
			}
			if(isset($value['minlength']) && (strlen($PostedValues[$key])<$value['minlength']))
			{
				if(isset($value["errminlength"]))
					$ErrorValues[$key]=$value["errminlength"];
				else
					$ErrorValues[$key]="Minimum Length must be " . $value['minlength'];
				$MyPostedValues[$key]="";
			}
			if(isset($value['maxlength']) && (strlen($PostedValues[$key])>$value['maxlength']))
			{
				if(isset($value["errmaxlength"]))
					$ErrorValues[$key]=$value["errmaxlength"];
				else
					$ErrorValues[$key]="Maximum Length must be " . $value['maxlength'];
				$MyPostedValues[$key]="";
			}
			if(isset($ErrorValues[$key]))
				$MyPostedValues[$key]="";
			else
				$MyPostedValues[$key]=$PostedValues[$key];
		}
		break;
	  case "fixedhtml":
	  	//break otherwise it will try to validate
		break;
	  case "birthday":
	     if(!isset($PostedValues[$key . "_day"]) || !isset($PostedValues[$key . "_month"]) || !isset($PostedValues[$key . "_year"] ))
				$ErrorValues[$key]="Form tampered with: missing part of date";
		 elseif(checkdate($PostedValues[$key . "_month"],$PostedValues[$key . "_day"],$PostedValues[$key . "_year"]))
		 {
			 $PostedValues[$key . "_month"]= (int) $PostedValues[$key . "_month"];
			 $PostedValues[$key . "_day"]= (int) $PostedValues[$key . "_day"];
			 $PostedValues[$key . "_year"]= (int) $PostedValues[$key . "_year"] ;
				$MyPostedValues[$key]=$PostedValues[$key . "_year"] . "/" . $PostedValues[$key . "_month"] . "/" . $PostedValues[$key . "_day"] ;
		 }
		 else
		 	$ErrorValues[$key]="Invalid date";
		 break;
      case "checklist":
       break;
	  case "email":
	  	if(!isset($PostedValues["$key"]) )
		{
			$ErrorValues[$key] ="Form tampered. field $key not provided";
		}
		else
		{

			if(strstr($PostedValues[$key],"\s\r\n"))
			{
				if(isset($value["errillegalcharacters"]))
					$ErrorValues[$key]=$value["errillegalcharacters"];
				else
					$ErrorValues[$key]="Illegal characters detected!";			
				$MyPostedValues[$key]="";
			}
			 if(!ereg("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$",$PostedValues[$key]))
			 {
				if(isset($value["error"]))
					$ErrorValues[$key]=$value["error"];
				else
					$ErrorValues[$key]="Invalid email";			
				$MyPostedValues[$key]="";
			 }
			 
		}
      default:
			if(!isset($PostedValues["$key"]) )
			{
				$ErrorValues[$key] ="Form tampered. field $key not provided";
			}
			else
			{
				if(isset($value['minlength']) && (strlen($PostedValues[$key])<$value['minlength']))
				{
					if(isset($value["errminlength"]))
						$ErrorValues[$key]=$value["errminlength"];
					else
						$ErrorValues[$key]="Minimum Length must be " . $value['minlength'];
					$MyPostedValues[$key]="";
				}
				if(isset($value['maxlength']) && (strlen($PostedValues[$key])>$value['maxlength']))
				{
					if(isset($value["errmaxlength"]))
						$ErrorValues[$key]=$value["errmaxlength"];
					else
						$ErrorValues[$key]="Maximum Length must be " . $value['maxlength'];
					$MyPostedValues[$key]="";
				}
			   if(isset($value["filter"]))
			   {
				 if(!isset($value["error"]))
					$value["error"]="(error)";
				 if(!ereg($value["filter"],$PostedValues[$key]))
				 {
		//			print("<font color=red>" . $value["error"] . "</font>") ;
					$ErrorValues[$key]=$value["error"];
					$MyPostedValues[$key]="";
			 	 }
		       }
			if(isset($ErrorValues[$key]))
				$MyPostedValues[$key]="";
			else
				$MyPostedValues[$key]=$PostedValues[$key];

	   }	
  }
	if(!isset($PostedValues[$key])) $PostedValues[$key]="";
	if(is_array($PostedValues[$key]))
	{
	  	$tmpValue="";
	    foreach($PostedValues[$key] as $Postedkey => $Postedvalue)
	    	$tmpValue .= "|" . $Postedvalue;
	    if($tmpValue) $tmpValue=substr($tmpValue,1);
	  }
	//else
	   // print "<b>$key :</b> " . $PostedValues[$key] . "<br>";
  }

  if(isset($ErrorValues) &&  is_array($ErrorValues) && (count($ErrorValues)>0) )
	{
		$retval["success"]=false;
		$retval["errors"]=$ErrorValues;
	}else
		   $retval["success"]=true;
  $retval["FormValues"]=$MyPostedValues;
  $retval["content"]=form_gen($FormVars,$MyPostedValues,$formname,"",false,$ErrorValues);
   return $retval;
}
function form_gen($FormVars,$FormValues,$formname,$action="",$display=false,$errors="")
{
  global $lstmonths;
  global $PHP_SELF;
  if($action=="") $action=$PHP_SELF;

	$retval="<table>\n";
 	$retval.="<form method=post action=$action><input type=submit name=action><p>"; 
     $showtr=true;
 	 foreach($FormVars as $key => $value)
	 {
	   
          if($showtr==true)
	        $retval .= "<tr>";
		  if(!isset( $FormValues[$key] )) 
		  {
		    if(isset($value["default"]))
				$FormValues[$key]=$value["default"];
			else
				$FormValues[$key]="";
		}
	    if(isset($value["label"]))
			$label=$value["label"];
		else
			$label=$key;
	    if(isset($value["bgcolor"]))
			$bgcolor="bgcolor=" . $value["bgcolor"];
		else
			$bgcolor="";
		if(isset($value["vals"]))
			$listvals=$value["vals"];
		if(isset($errors[$key]))
			$theError="<font color=brown>" . $errors[$key] . "</font>";
		else
			$theError="";
if(isset($value["colspan"]))
    $colspan="colspan=" . $value["colspan"];
else
    $colspan="";
		switch(strtolower($value["type"]))
		{
		  case "currency":
    $retval .= "<td $bgcolor $colspan><table width=100% $bgcolor><tr>\n";
		     $retval .=  "<td >$label </td><td><input type=text name=\"$key\" value=\"". $FormValues[$key] ."\"> $theError</td>";
    $retval .= "\n</tr></table></td>\n\n";
		     break;
		  case "birthday":
    $retval .= "<td $colspan><table  width=100% $bgcolor><tr>\n";
	if(isset($FormValues[$key]))
		$targetdate=$FormValues[$key];
	elseif(isset($value["default"]))
	{
		$targetdate=$value['default'];
	}
	else 
		$targetdate="1980/17/8";

		$i=0;
		if(isset($value['months']))
		{
			foreach($value['months'] as $monthkey)
			{
				$i++;
				$myMonths[$i]=$monthkey;
			}
		}
		else
			$myMonths=$lstmonths;
			 list($key_year,$key_month,$key_day)=split("\/",$targetdate);
             $key_year=(int)$key_year;
             $key_month=(int)$key_month;
             $key_day=(int)$key_day;
			 $retval .= "$label";
 			 $retval .=  selectbox( $myMonths,$key . "_month",$key_month);
             for($i=1;$i<32;$i++)
               $lstdays[$i]=$i;
 			 $retval .=  selectbox(  $lstdays,$key . "_day",$key_day);
             for($i=1950;$i<2000;$i++)
               $lstyears[$i]=$i;
 			 $retval .=  selectbox(  $lstyears,$key . "_year",$key_year) . $theError;

     $retval .= "\n</tr></table></td>\n\n";
		     break;
		  case "email":
		  case "text":
    $retval .= "<td $colspan><table width=100% $bgcolor><tr>\n";
		     $retval .=  "<td>$label </td><td><input class=inputbox type=text name=\"$key\" value=\"". $FormValues[$key] ."\"> $theError</td>";
    $retval .= "\n</tr></table></td>\n\n";
		     break;
		  case "fixedhtml":
    $retval .= "<table width=100% $bgcolor ><tr>\n";
		     $retval .=  "<td $bgcolor>" . $value["value"] . "</td>";
    $retval .= "\n</tr></table></td>\n\n";
		     break;
		  case "setpassword":
		  
    $retval .= "<td $colspan><table width=100% $bgcolor><tr>\n";
		     $retval .=  "<td>$label </td><td><input class=inputbox type=password size=8 name=\"$key\" value=\"\"><input type=password size=8 class=inputbox name=\"" . $key . "passcheck\" value=\"\"> $theError</td>";
    $retval .= "\n</tr></table></td>\n\n";
		     break;
		  case "textarea":
    $retval .= "<td $colspan><table><tr>\n";
      if(isset($value["cols"]))
            $cols=" cols=".$value['cols'];
      else
            $cols="";

      if(isset($value["rows"]))
            $rows=" rows=".$value['rows'];
      else
            $rows="";

		     $retval .=  "<td>$label </td><td><textarea $cols $rows name=\"$key\">". $FormValues[$key] ."</textarea> $theError</td>";
    $retval .= "\n</tr></table></td>\n\n";
		     break;
		  case "hidden":
		  	$retval .=  "<input type=hidden name=\"$key\" value=". $FormValues[$key] .">";
		  	break;
		  case "gfxdir":
		     $retval .= "<td $colspan>$label </td><td>";
			 $retval .=  gfx_file_radio($value["dir"],$key,$FormValues[$key]);
			 $retval .= "</td>$theError</td>";
			 break;
		  case "dirselect":
		     $retval .= "<td $colspan>$label </td><td>";
			 $retval .=  dir_select($value["dir"],$key,$FormValues[$key]);
			 $retval .= "</td>$theError</td>";
			 break;
		  case "fileslist":
    $retval .= "\n<td $colspan><table><tr>";
		     $retval.= "<td>$label </td><td>";
			 $retval .=  file_selectbox($value["dir"],$key,$FormValues[$key]);
			 $retval .=  "</td><td>$theError</td>";
    $retval .= "</tr></table></td>\n\n";
			 break;
		  case "list":
    $retval .= "<td $colspan><table><tr>\n";

		     $optval="";
		     if(isset($value["size"])) $optval .= " size='" . $value["size"] . "' ";
		     if(!isset($value["default"])) $value['default']="";
		     $retval.= "<td>$label </td><td>";
			 $retval .=  selectbox( $listvals ,$key,$value['default'],$optval);
			 $retval .=  " $theError</td>";
    $retval .= "\n</tr></table></td>\n\n";
			break;
		  case "matrix":
    $retval .= "<td $colspan><table><tr>\n";
		     $optval="";
		     if(isset($value["size"])) $optval .= " size='" . $value["size"] . "' ";
		     if(!isset($value["default"])) $value['default']="";
		     $retval.= "<td>$label </td><td>";
			 $retval .=  matrix( $listvals,$value['matrix'] ,$key,$value['default'],$optval);
			 $retval .=  " $theError</td>";
    $retval .= "\n</tr></table></td>\n\n";
			break;
		  case "checklist":
    $retval .= "<td $colspan><table><tr>";
  		     $retval.= "<td>$label </td><td>";

			$chkcount=0;
	  	    $retval .= "<table><tr>";
		  	foreach($value["vals"] as $chkKey => $chkValue)
		  	{
		  	  $chkcount++;
		  	  $retval .= "<td>";
		  	$retval .= "<input type=checkbox name=\""  . $key . "[]\" value=\"$chkKey\" />$chkValue \n";
		  	  $retval .= "</td>";
		  	 if(!($chkcount % 5))
		  	 	$retval .= "</tr><tr>";
		  	}
	  	    $retval .= "</tr></table>";
			$retval .= "</td></tr>";
    $retval .= "</tr></table></td>";
		  	break;


		 case "optionlist":
    $retval .= "<td $colspan><table><tr>";

		     $optval="";
		     if(isset($value["size"])) $optval .= " size='" . $value["size"] . "' ";
		     if(!isset($value["default"])) $value['default']="";
		     $retval.= "<td>$label </td><td>";
			 $retval .=  optionlist( $listvals ,$key,$value['default'],$optval);
			 $retval .=  " $theError</td>";
    $retval .= "</tr></table></td>";
			break;

		}
if(isset($value["skiptr"]) and ($value["skiptr"]==true))
    $showtr=false;
else
{
	   $retval .= "</tr>";
       $showtr=true;
}
	 }
		$retval .= "</table>";
	
  if($display==true) print $retval;

  return $retval;

}
function ShowEntireArray($array,$root)
{
  foreach (array_keys($array) as $element)
  {
    $my_array=$array[$element];
    if(is_array($my_array))
    {
      ShowEntireArray($my_array,$root . "[" . $element . "]");
    }
    else
    {
      echo($root . "[" . $element . "]=" . $array[$element]);
    }
  }
} 
function selectbox($Values, $SelName,$Default="",$OptVars="")
{
    if($SelName<>"") $SelName="name=$SelName";
	$selBox="<select $SelName $OptVars>";
	if($Values)
	{
	    foreach($Values as $key => $value)
	    {
	       if($Default==$key)
	         $selBox .= "<option value='$key' selected>$value</option>\n";
	       else
	         $selBox .= "<option value='$key'>$value</option>\n";

	    }
    }
    $selBox .="</select>";
	return $selBox;
}
function matrix($Values, $MatrixValues, $SelName,$Default="",$OptVars="")
{

    if($SelName<>"") 
    	$SelName="name=$SelName";
	if($Values)
	{
     $selBox .= "\n\n<table><tr><td>$key</td>"  ;
	    foreach($MatrixValues as $matrixkey => $matrixvalue)
	    {
           $selBox .= "<td>$matrixvalue&nbsp;&nbsp;</td>\n";
        }
        $selBox .= "</tr>";
        foreach ($Values as $key => $value)
        {
            $selBox .= "\n<tr><td>$value</td>\n";
	      foreach($MatrixValues as $matrixkey => $matrixvalue)
	      {
	       if(1==1)
	         $selBox .= "  <td>\n    <input type=radio checked name='$SelName_$matrixvalue' value='$matrixkey' /></td>\n";
	       else
	         $selBox .= "  <td>\n     <input type=radio name='$SelName' value='$key' /></td>\n";
          }
        }
        $selBox .= "</tr></table>";

    }

	return $selBox;
}
function optionlist($Values, $SelName,$Default="",$OptVars="",$linecount=4)
{

    if($SelName<>"") $SelName="name=$SelName";
	$selBox="<table><tr>";
	if($Values)
	{
	  	$tmpCount=0;
	    foreach($Values as $key => $value)
	    {
	      $tmpCount++;
	       if($Default==$key) 
	         $selBox .= "<td><label> <input type=radio checked name='$SelName' value='$key' />
    $value</label></td>\n";
	       else
	         $selBox .= "<td><label> <input type=radio name='$SelName' value='$key' />
    $value</label></td>\n";		
		if(!($tmpCount % $linecount)) $selBox .="</tr><tr>\n";
	    }
    }
    $selBox .="</select></tr></table>";
	return $selBox;
}

function file_selectbox($FileDir,$SelName,$Default="",$OptVars="")
{
    if($SelName<>"") $SelName="name=$SelName";
	$selBox="<select $SelName $OptVars>";
	if ($handle = opendir($FileDir) )
	{

	   while (false !== ($file = readdir($handle))) {
        echo $file . "<br>";
	       if ($file != "." && $file != ".."  ) {
	       	   list($filename,$filextension)=split("\.",$file);
	       	   if(strtolower($filextension)=="gif")
	       	   if($Default==$file) 
		           $selBox .= "<option value='$file' selected>$filename</option>\n";
	       	   else
		           $selBox .= "<option value='$file'>$filename</option>\n";
	       }
	   }
	   closedir($handle);
	}
	$selBox .= "</select>";
	return $selBox;
}   /*
function dir_select()
{

if ($handle = opendir('../themes')) {
   while (false !== ($file = readdir($handle))) {
       if ($file != "." && $file != ".." && is_dir("../themes/$file") ) {

       	   if($pref_theme==$file)
	           echo "<option value='$file' selected>$file</option>\n";
		   else
	           echo "<option value='$file'>$file</option>\n";
       }
   }
   closedir($handle);
}

} */
function dir_select($FileDir,$boxName,$Default="",$Columns=5,$OptVars="")
{
  $count=0;
    if($boxName<>"") $boxName="name=$boxName";
	$selBox="<select $boxName>";
	if ($handle = opendir($FileDir) )
	{
	   while (false !== ($file = readdir($handle))) {
           if ($file != "." && $file != ".." && is_dir("$FileDir/$file") ) {
	       	   list($filename,$filextension)=split("\.",$file);
       	   if($pref_theme==$file)
	           $selBox .= "<option value='$file' selected>$file</option>\n";
		   else
	           $selBox .= "<option value='$file'>$file</option>\n";
	       	   if($Default==$file)
	           $selBox .= "<option value='$file' selected>$file</option>\n";
		   else
	           $selBox .= "<option value='$file'>$file</option>\n";
	       	 $count++;
	       	 if(($count%$Columns)==$Columns-1) $selBox .="<br>\n";

		   }
	   }
	   closedir($handle);
	}
	$selBox .= "</select>";
	return $selBox;
}
function gfx_file_radio($FileDir,$boxName,$Default="",$Columns=5,$OptVars="")
{
  $count=0;
    if($boxName<>"") $boxName="name=$boxName";
	$selBox="";
	if ($handle = opendir($FileDir) )
	{
	   while (false !== ($file = readdir($handle))) {
	       if ($file != "." && $file != ".."  ) {
	       	   list($filename,$filextension)=split("\.",$file);
	       	   if(strtolower($filextension)=="gif" or strtolower($filextension)=="jpg")
	       	   if($Default==$file) 
	       	     $selBox .= "  <input type='radio' $boxName $OptVars value='$file' checked /><img src=$FileDir/" . rawurlencode($file) . ">\n";
	       	   else
	       	     $selBox .= "  <input type='radio' $boxName $OptVars value='$file' /><img src=$FileDir/" . rawurlencode($file) . ">\n";
	       	 $count++;
	       	 if(($count%$Columns)==$Columns-1) $selBox .="<br>\n";

		   }
	   }
	   closedir($handle);
	}
	$selBox .= "</select>";
	return $selBox;
}
?>

