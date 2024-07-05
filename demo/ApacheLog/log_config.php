<?php
$config["columns"]=array("date","ip","hostname","path","referer","agent","host");
//$config["columns"]=array(0=>"date",1=>"ip",2=>"hostname",3=>"path",4=>"referer",5=>"agent",6=>"host");
$config["columns"]=array(0=>"date",6=>"host",3=>"path",2=>"hostname",4=>"referer",1=>"ip",5=>"agent");
$config["file"]="$base_dir/vl/davisitors.txt";

$config["customgraphs"]=array("ip","path","host","agent","hostname","referer");

