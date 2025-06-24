<?php
namespace Opensitez\Simplicity;
use Symfony\Component\Yaml\Yaml;

$colorCycle=array("darkblue","black","blue","green","darkgreen","darkred","red","orange");
class LogHelper
{
    private $filename;
    private $filters;
    private $total_engines;
    private $total_tools=0;
    private $total_scans=0;
    private $total_others=0;
    private $total_visitors;
    private $engine_stats=array('Visitors'=>0,'Scan'=>0,'Engine'=>0,'Tool'=>0);
    private $parsedLog;
    private $filteredLog;
    private $ruleStats;
    private $columns;
    private $rules;
    private $families;
    private $filter_count;
    private $domaincache;	
    private $customGraphs;
    private $customGraph_results;
    private $results;
    private $graphid=0;
    private $config_object;
    private $defaultcolors=array("darkblue","black","blue","green","darkgreen","darkred","red","orange");
    //constructor
    function __construct($config)
    {
        if(is_array($config)) {
            $this->config_object = new \Opensitez\Simplicity\Config($config);
        } else if(is_object($config))  {
            $this->config_object = $config;
        } else {
            $this->config_object = new \Opensitez\Simplicity\Config();
        }
        $this->init();
        $this->ruleStats=array();
    }    //reinitialize all values when file name changes... not sure if necessary... but always good
    function init()
    {

        $this->filename=$this->config_object->get('filename');
        //$this->engines_file=$this->config_object->get('engines');
        $this->total_engines=0;
        $this->total_visitors=0;
        $this->total_others=0;
        $this->filter_count=0;
        $this->total_engines=0;
        $this->total_tools=0;
        $this->parsedLog="";
        $this->ruleStats="";
        $this->results=array();
        
        // Set default column mappings if not already set
        if (!isset($this->columns)) {
            $this->setDefaultColumns();
        }
    }
    
    function setDefaultColumns()
    {
        $logformat = $this->config_object->get('logformat') ?? "apache";
        
        if ($logformat === "apache" || $logformat === "nginx" || $logformat === "combined") {
            // Apache Common Log Format / Combined Log Format
            $this->columns = [
                0 => ['ip', 'IP Address'],
                1 => ['ident', 'Identity'],
                2 => ['user', 'User'],
                3 => ['date', 'Date/Time'],
                4 => ['request', 'Request'],
                5 => ['status', 'Status Code'],
                6 => ['size', 'Response Size'],
                7 => ['referer', 'Referer'],
                8 => ['user_agent', 'User Agent']
            ];
        } else {
            // Tab-separated format
            $this->columns = $this->config_object->get('columns', [
                0 => ['date', 'Date'],
                1 => ['ip', 'IP'],
                2 => ['hostname', 'Hostname'],
                3 => ['path', 'Path'],
                4 => ['referer', 'Referer'],
                5 => ['user_agent', 'User Agent'],
                6 => ['domain', 'Domain']
            ]);
        }
    }
    function setFilter($filter_field,$filter_type,$filter_value)
    {
        $this->rules["filters"][]=array("field"=>$filter_field,"type"=>$filter_type,
            "value"=>$filter_value,"name"=>"$filter_field $filter_type $filter_value","class"=>"filters");
    }
    function setCustomGraphs($customGraphs)
    {
        $this->customGraphs=$customGraphs;
    }
    function showCustomGraphs()
    {
        $graphcount=0;
        foreach($this->customGraphs as $key=>$value)
        {
            $this->showCustomGraph($value[0]); print "&nbsp;";
            if($graphcount%2) print("<p>");
            $graphcount++;
        }
    }
    function showCustomGraph($graph_name)
    {
        if(isset($this->customGraph_results[trim($graph_name)]))
        {
            arsort($this->customGraph_results[$graph_name]);
            if(count($this->customGraph_results[$graph_name])>10)
            {
                $first_ten = array_slice($this->customGraph_results[$graph_name],0, 14);
                $others = array_slice($this->customGraph_results[$graph_name], 14,null,true);
                $others_total=0;
                $value=0;
                foreach($others as $okey=>$ovalue)
                {
                    $others_total += $ovalue;
                }
                $first_ten["Others"]=$others_total;
            }
            else
            {
                $first_ten=$this->customGraph_results[trim($graph_name)];
            }
            $label=urlencode(implode("*",array_keys($first_ten)));
            $data=implode("*",array_values($first_ten));
            /*print "<img src=graph/3dpie.php?data=$data&label=$label&show_totals=true>";*/
            $this->d3pie("first_ten",$first_ten,$label);
        }
    }    function d3pie($name,$data,$label) {
        if(!isset($this->graphid))
            $this->graphid=0;
        else 
            $this->graphid +=1;

        $template = new \Opensitez\Simplicity\SimpleTemplate();
        $templatePath = __DIR__ . '/templates/graph.tpl';
        $template->setFile($templatePath);
        
        // Calculate total for percentages
        $total = array_sum($data);
        
        // Build data list HTML
        $dataList = '';
        foreach($data as $key=>$value) {
            $dataList .= "<div><span style='font-weight:bold;color:#833;min-width:200px;display:inline-block;'>" . htmlentities($key) . "</span> : &nbsp; <span style='color:#aaa;'>$value</span></div>\n";
        }
        
        // Build dataset for D3
        $dataset = '';
        foreach($data as $key=>$value) {
            $dataset .= "{ label: '" . htmlentities($key) . "', count: " . intval($value*360/$total) . "} , \n";
        }
        
        $variables = [
            'GRAPH_ID' => "graph" . $this->graphid,
            'DATA_LIST' => $dataList,
            'DATASET' => $dataset
        ];
        
        $template->setVars($variables);
        echo $template->render();
    }
    function setColumns($columnNames)
    {
        $this->columns=$columnNames;
    }    function loadRules($category='engines')
    {
        $filename = $this->config_object->get('paths.engine_file');
        //print $filename;exit;
        // Use Config class to load the YAML file into the 'engines' config key
        if ($filename && $this->config_object->mergeYaml('engines', $filename)) {
            $rules = $this->config_object->get('engines.rules', []);
            
            foreach ($rules as $rule) {
                if ($rule['class'] === $category) {
                    $this->rules[$category][] = [
                        "class" => $rule['class'],
                        "field" => $rule['field'],
                        "value" => $rule['name'],
                        "type" => "like",
                        "name" => $rule['description']
                    ];
                }
            }
        }
    }
    function printLog()
    {
        $template = new \Opensitez\Simplicity\SimpleTemplate();
        $templatePath = __DIR__ . '/templates/log.tpl';
        $template->setFile($templatePath);
        
        $output = '';
        if($this->filter_count>1) {
            $output .= $this->filter_count . " lines filtered<p>";
        }
        
        $variables = [
            'LOG_ENTRIES' => $this->filteredLog
        ];
        
        $template->setVars($variables);
        echo $output . $template->render();
    }
    function splitTabbedLine($line)
    {
        return explode("\t", $line);
    }    function splitApacheLine($line)
    {
        $fields = [];
        $position = 0;
        $length = strlen($line);
        
        while ($position < $length) {
            // Skip whitespace
            while ($position < $length && $line[$position] === ' ') {
                $position++;
            }
            
            if ($position >= $length) break;
            
            $value = '';
            
            // Check for bracketed content (like dates)
            if ($line[$position] === '[') {
                $position++; // Skip opening bracket
                while ($position < $length && $line[$position] !== ']') {
                    $value .= $line[$position];
                    $position++;
                }
                if ($position < $length) $position++; // Skip closing bracket
            }
            // Check for quoted content
            elseif ($line[$position] === '"') {
                $position++; // Skip opening quote
                while ($position < $length && $line[$position] !== '"') {
                    if ($line[$position] === '\\' && $position + 1 < $length) {
                        $position++; // Skip escape character
                    }
                    $value .= $line[$position];
                    $position++;
                }
                if ($position < $length) $position++; // Skip closing quote
            }
            // Regular field (no spaces)
            else {
                while ($position < $length && $line[$position] !== ' ') {
                    $value .= $line[$position];
                    $position++;
                }
            }
            
            $fields[] = $value;
        }
        
        return $fields;
    }
    function showGraphs()
    {
        $numgraph=0;
        if (isset($this->filteredLog) && strlen($this->filteredLog)>0)
        {
/*
                $labels="Engines (". $this->total_engines . ")*Visitors (".$this->total_visitors .")";
                                $labels.="*Tools (0)";

                                $labels.="*Scans (".$this->total_scans .")";
                                $labels=urlencode($labels);
                $data=$this->total_engines . "*" . $this->total_visitors."*".$this->total_tools."*".$this->total_scans;
 */
            $label=urlencode(implode("*",array_keys($this->engine_stats)));
            $data=implode("*",array_values($this->engine_stats));
            /*print "<img src=graph/3dpie.php?data=$data&label=$label&show_totals=true>";*/
            $numgraph +=1;
            $this->d3pie("graph".$numgraph,$this->engine_stats,$label,true);
            if(is_array($this->ruleStats))
            {
                //print "<img src=graph/3dpie.php?data=$data&label=$labels&color_scheme=CCBB88*55BBBB*AABBCC*5588CC>";
                //print "img src=graph/3dpie.php?data=$data&label=$labels&color_scheme=CCBB88*55BBBB*AABBCC*5588CC";

                arsort($this->ruleStats);
                if(count($this->ruleStats)>10)
                {
                    $first_ten = array_slice($this->ruleStats,0, 14);
                    $others = array_slice($this->ruleStats, 14,null,true);
                    $others_total=0;
                    $value=0;
                    foreach($others as $okey=>$ovalue)
                    {
                        $others_total += $ovalue;
                    }
                    $first_ten["Others"]=$others_total;
                }
                else
                {
                    $first_ten=$this->ruleStats;
                }
                if(is_array($first_ten))
                {
                    $label=urlencode(implode("*",array_keys($first_ten)));
                    $data=implode("*",array_values($first_ten));
                    print "&nbsp;";
                    $this->d3pie("tenarray",$first_ten,$label,true);
                      
                }
            }
        }
        else
        {
            if(isset($this->rules["filters"]) && is_array($this->rules["filters"]))
                print("No results. Try without a filter");
            else
                print("No results. Log must be empty");
        }
        //exit;
    }
    function parseLog()
    {

        $filename = $this->config_object->get('file');
        print $filename . "<br>";
        if ( !file_exists($filename)) {
            print("Log file " . $this->filename . " does not exist or is inaccessible.");
            return false; // Return false if the file does not exist
        }

        $logfile = file_get_contents($filename);
        if ($logfile === false) {
            print("Failed to read log file " . htmlentities($filename) . ".");
            return false; // Return false if the file cannot be read
        }

        $log_lines = explode("\n", $logfile);
        $colorCycle = $this->config_object->get('colorCycle') ?? $this->defaultcolors;
        $this->filteredLog = ("<table border=1>");
        $idx=0;

        $this->filteredLog = ("<table border=1>");

        $logformat = $this->config_object->get('logformat') ?? "apache";
        $logformat = $this->config_object->get('logformat')??"apache";

        
        foreach($log_lines as $line)
        {
            if(trim($line<>""))
            {
                //$line=str_replace(array("index.php","blog/wp-login","wordpress/wp-login","/wp/"), array("","wp-login","wp-login",'/wordpress/'), $line);
                if( $logformat=="apache" || $logformat=="nginx" || $logformat=="combined" )
                {
                    $logline = $this->splitApacheLine($line);
                }
                else 
                {
                    $logline=$this->splitTabbedLine($line);
                } 
                $entry=array();
                $curcolor=0;
                $coloredLine="";
                $idx+=1;
                foreach($this->columns as $col_id=>$col_details)
                {

                    $col_name = $col_details[0] ?? $col_id;
                    if(isset($logline[$col_id]))
                      $entry[$col_name]=trim($logline[$col_id]);
                    else
                      $entry[$col_name]="";

                    $color = $colorCycle[$curcolor % count($colorCycle)];

                    $entry_var =htmlentities( trim( $logline[$col_id]??"" ) );
                    $coloredLine .="<font color=" . $color . ">" . $entry_var . "</font>&nbsp;\t\n";
                    $curcolor++;
                }
                $coloredLine .= "<br/>";
                //get domain name for visiting host
/*					$mySplitDomain=explodedomain($entry["hostname"]);
                    $entry["hostname_short"]=$mySplitDomain["domain"];
                    $entry["v_isip"]=$mySplitDomain["isIP"];

                    //get domain name for http_host
                    $mySplitDomain=explodedomain($entry["domain"]);
                    $entry["domain_short"]=$mySplitDomain["domain"];
 */				
                //check if it is an engine
                $isEngine=$this->match_rule($entry,"engines");
                $isScan=$this->match_rule($entry,"scan");
                $isTool=$this->match_rule($entry,"tool");
                //check if this line is to be filtered
                $isFilter=$this->match_rule($entry,"filters");

                //check if this is part of a family
                $isFamily=$this->match_rule($entry,"families");

                //print("<pre>");
                if($isScan) {
                    $this->total_scans++;
                }
                if($isTool) {
                    $this->total_tools++;
                }

                if(isset($this->rules["filters"]) && $isFilter===false)
                {
                    $this->filter_count++;
                }
                else
                {
                    if($isEngine===false)
                    {
                        $this->filteredLog = $coloredLine . "<br>" . $this->filteredLog;
                        $this->total_visitors++;
                        @$this->engine_stats['Visitors']++;
                    }
                    else
                    { @$this->engine_stats[ucfirst($this->rules["engines"][$isEngine]['class'])]++;
                    switch($this->rules["engines"][$isEngine]['class']) {
                    case "engine":
                        $this->total_engines ++;
                        break;
                    case "tool":
                        $this->total_tools ++;
                        break;
                    case "scan":
                        $this->total_scans ++;
                        break;
                    default:
                        $this->total_others ++;
                        break;

                    } 
                    $this->total_engines ++;
                    $rule_name=$this->rules["engines"][$isEngine]['name'];
                    if(isset($this->ruleStats[trim($rule_name)]))
                        $this->ruleStats[trim($rule_name)]++;
                    else
                        $this->ruleStats[trim($rule_name)]=1;
                    }
                    if($isScan) {
                        $this->total_scans++;
                    }
                    if($isTool) {
                        $this->total_tools++;
                    }
                    if($isEngine==false)
                    {
                        //check the custom graphs
                        foreach($this->customGraphs as $graph_name=>$graph_rule)
                        {
                            if(isset($entry[$graph_rule[0]]))
                                $graph_value=trim($entry[$graph_rule[0]]);
                            if(isset($grap_value)) {
                              if(isset($this->customGraph_results[$graph_rule[0]][$graph_value]))
                                $this->customGraph_results[$graph_rule[0]][$graph_value]++;
                              else
                                $this->customGraph_results[$graph_rule[0]][$graph_value]=1;
                            }
                        }
                    }
                }
                if(isset($this->rules["families"]) && $isFamily===false ) {
                    @$rule_name=$this->results["families"][$isEngine]['name'];
                    if(isset($this->results['family_types'][trim($rule_name)]))
                        $this->results['family_types'][trim($rule_name)]++;
                    else
                        $this->results['family_types'][trim($rule_name)]=1;
                }
                //						print("<font color=red>Rule matched, #$isEngine</font><br>");
                //	print_r($entry);
                //print("<hr>");
            }
        }
                    /*
                    list($vDate,$vIP,$vIPName,$vPath,$vReferer,$vAgent,$vDomain)=explode("\t",$line);


                    $referer_keys=array_keys($engine_referers);
                    //print_r($referer_keys);

                    if (!isset($engines[strtolower($vIPDomain)]))
                    {
                        if (!match_array($referer_keys,$vAgent))
                        {
                                                   print $line . "<br>";
                                                   $regulartotal++;
                        }
                        else
                        {
                            $enginestotal++;
                        }

                    } 
                    else
                        $enginestotal++;

                    //count number of visits per domain
                    if(!isset($domaincount[$vDomain])) $domaincount[$vDomain]=0;
                    $domaincount[$vDomain]++;

                    //count number of visits per IP
                    if(!isset($hostcount[$vIPDomain])) $hostcount[$vIPDomain]=0;
                    $hostcount[$vIPDomain]++;

                    if (!isset($hostdomaincount[$vIPDomain] [$vDomain] ) ) 
                        $hostdomaincount[$vIPDomain][$vDomain]=0;
                    $hostdomaincount[$vIPDomain][$vDomain]++;

                    if($vReferer<>"Unknown" && trim($vReferer) <>"")
                    {
                    //	print "<tr>";	
                    //foreach($values as $key) print("<td>$key</td>");
                    // print("<td>$vDomain</td><td>$vIPName</td><td>'$visIP'</td><td>$vIPDomain</td><td>'$vReferer'</td>");
                     // print "</tr>";	
                    } 
                }
            }
            asort($domaincount);
            $domaincount=array_reverse($domaincount,true);
                    print("<b>Engines</b>: $enginestotal <b>Visitors</b>: $regulartotal");

                    asort($hostcount); */
    }
    function match_rule( $log_entry, $ruleset="engines" )
    {
        $retval=false;
        if(isset($this->rules[$ruleset]))
        {
            foreach($this->rules[$ruleset] as $rule_id=>$rule)
            {
                $ref_string=$rule["value"];
                switch($rule["type"])
                {
                case "like":
                    if(@preg_match("|$ref_string|i",$log_entry[$rule["field"]]))
                    {
                        $retval=$rule_id;
                        //print("$ruleset match $ref_string like (" . $log_entry[$rule["field"]] . " (" . $rule["field"] . ")<br>");
                    } 
                }
            }
        }
        //print("retval: $retval");
        return $retval;

    }
    function match_filter( $log_entry)
    {
        $retval=false;
        foreach($this->filters as $rule_id=>$rule)
        {
            $ref_string=$rule["value"];
            if(preg_match("|$ref_string|i",$log_entry[$rule["field"]]))
            {
                $retval=$rule_id;
            } 
        }
        //print("retval: $retval");

        //	exit;
        return $retval;

    }
}

?>
