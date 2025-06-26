<?php
namespace Opensitez\Simplicity\Plugins;
use Symfony\Component\Yaml\Yaml;

$colorCycle=array("darkblue","black","blue","green","darkgreen","darkred","red","orange");
class LogHelper extends \Opensitez\Simplicity\Plugin
{
    private $filename;
    private $filters;
    private $total_engines;
    private $total_tools=0;
    private $total_scans=0;
    private $total_others=0;
    private $total_visitors;
    private $default_stats=['Visitors'=>0,'Scan'=>0,'Engines'=>0,'Tool'=>0];
    private $engine_stats;
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
    private $defaultcolors=array("darkblue","black","blue","green","darkgreen","darkred","red","orange");

    function init()
    {
        $this->filename=$this->config_object->get('filename');
        $this->total_engines=0;
        $this->total_visitors=0;
        $this->total_others=0;
        $this->filter_count=0;
        $this->total_engines=0;
        $this->total_tools=0;
        $this->parsedLog="";
        $this->ruleStats=array(); // Changed from "" to array()
        $this->results=[];

        $this->engine_stats = $this->default_stats;
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
    function setColumns($columnNames)
    {
        $this->columns=$columnNames;
    }    
    function loadRules($category='engines')
    {
        $filename = $this->config_object->get('paths.engine_file');
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
        $logView = $this->plugins->get_plugin('logviewblock');
        echo $logView->render([
            'log_entries' => $this->filteredLog,
            'columns' => $this->columns,
            'filter_count' => $this->filter_count,
            'color_cycle' => $this->config_object->get('colorCycle') ?? $this->defaultcolors
        ]);
    }

    function parseLog()
    {

        $filename = $this->config_object->get('file');
        if (!$filename) {
            print("Log file not specified in configuration.");
            return false;
        }
        if ( !file_exists($filename)) {
            print("Log file " . $this->filename . " does not exist or is inaccessible.");
            return false;
        }

        $logfile = file_get_contents($filename);
        if ($logfile === false) {
            print("Failed to read log file " . htmlentities($filename) . ".");
            return false;
        }

        $log_lines = explode("\n", $logfile);
        $colorCycle = $this->config_object->get('colorCycle') ?? $this->defaultcolors;
        $this->filteredLog = ("<table border=1>");
        $idx=0;

        $logformat = $this->config_object->get('logformat') ?? "combined";

        // Get Apache log parser plugin
        $logParser = $this->get_plugin('apachelogline');
        if (!$logParser) {
            $logParser = new \Opensitez\Simplicity\Plugins\ApacheLogLine($this->config_object);
        }

        if($log_lines) {
            $first = $logParser->splitCustomLine($log_lines[0]);
            $this->columns = array_keys($first);
        }
        foreach($log_lines as $line)
        {
            if(trim($line) != "")
            {
                if( $logformat=="space" )
                {
                    $logline = $logParser->splitWhitespaceLine($line);
                } elseif ($logformat=="combined" || $logformat=="vhost_combined"|| $logformat == "common")
                {
                    $logline = $logParser->splitCustomLine($line,$logformat);
                    $entry = $logline;
                    $coloredLine = "";
                    $curcolor=0;
                    $idx=0;
                    foreach($logline as $key=>$value)
                    {
                        $idx++;
                        $color = $colorCycle[$idx % count($colorCycle)];
                        $coloredLine .= "<font color=" . $color . ">" . htmlentities(trim($value)) . "</font>&nbsp;\t\n";
                    }
                }
                else 
                {
                    $logline=$logParser->splitTabbedLine($line);
                } 
                if(!$entry) {
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

                }
                $coloredLine .= "<br/>";

                $isEngine=false;
                $isTool=false;
                $isScan=false;
                $isFilter=false;
                $isFamily=false;
                $rule = $this->match_rule($entry);
                if($rule===false) {
                    $isEngine=false;
                } else {
                    $isEngine=true;

                    switch($rule['class']) {
                        case "engine":
                            $isEngine=true;
                            $this->total_engines++;
                            break;
                        case "tool":
                            $isTool=true;
                            $this->total_tools++;
                            break;
                        case "scan":
                            $isScan=true;
                            $this->total_scans++;
                            break;
                        case "filter":
                            $isFilter=true;
                            $this->filter_count++;
                            break;
                        default:
                            $isEngine=false;
                            break;
                    }

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
                    { 
                        @$this->engine_stats[ucfirst($rule['category'])]++;
                        $this->total_engines ++;
                        $rule_name=$rule['name'];
                        $newstat = $this->ruleStats[trim($rule_name)]??0;
                        $this->ruleStats[trim($rule_name)] = $newstat+1;

                    }
                    if($isEngine==false)
                    {
                        //check the custom graphs
                        foreach($this->customGraphs ?? [] as $graph_name=>$graph_rule)
                        {
                            //print "<br>Graph: {$graph_rule['field']}<br>";
                            if(isset($entry[$graph_rule["field"]]))
                                $graph_value=trim($entry[$graph_rule["field"]]);
                            if(isset($graph_value)) {
                              if(isset($this->customGraph_results[$graph_rule["field"]][$graph_value])) {
                                $this->customGraph_results[$graph_rule["field"]][$graph_value]++;
                              } 
                              else {
                                $this->customGraph_results[$graph_rule["field"]][$graph_value]=1;
                              }
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
            }
        }
    }
    function match_rule( $log_entry )
    {
        $retval=false;
        $rules = $this->config_object->get('engines', []);
        //print_r($rules['engines']);exit;
        //print_r($log_entry);exit;
        foreach($rules as $category=>$ruleset)
        {
            foreach($rules[$category] as $rule_id=>$rule)
            {
                $ref_string=$rule["value"];
                switch($rule["type"] ?? "like")
                {
                case "like":
                    if(@preg_match("|$ref_string|i",$log_entry[$rule["field"]]))
                    {
                        $retval= $rule;
                        $retval["rule"]="$category.$rule_id";
                        $retval["category"]=$category;
                        return $retval;
                    } 
                }
            }

        }

        return false;

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
    function showAllGraphs()
    {
        print "<style>
        .graphs-container {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            justify-content: center;
        }
        .graph {
            width: 300px;
            min-height: 300px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 7px;
            text-align: center;
        }
        .graph-canvas {
            width: 100%;
            height: auto;
        }
        @media (max-width: 768px) {
            .graph {
                width: 100%;
                margin: 0;
            }
        }
    </style>";

        print "<div class='graphs-container'>";
        $pieChart = $this->get_plugin('piechartblock');
        $barChart = $this->get_plugin('barchartblock');
        $vbarChart = $this->get_plugin('vbarchartblock');
        $lineChart = $this->get_plugin('linechartblock');
        
        if ($pieChart && isset($this->filteredLog) && strlen($this->filteredLog) > 0) {
            // Engine Stats - default to pie chart
            echo $pieChart->render([
                'data' => $this->engine_stats,
                'title' => 'Engine Stats',
                'graphId' => 'graph1',
                'limit' => 10
            ]);

            // Rule Stats - default to pie chart
            if (is_array($this->ruleStats)) {
                arsort($this->ruleStats);
                echo $pieChart->render([
                    'data' => $this->ruleStats,
                    'title' => 'Rule Stats',
                    'graphId' => 'tenarray',
                    'limit' => 10
                ]);
            }

            // Custom graphs - check type, default to pie
            foreach ($this->customGraphs ?? [] as $key => $value) {
                $title = $value['label'] ?? "Custom Graph";
                $type = $value['type'] ?? 'pie';
                $xlabel = $value['xlabel'] ?? '';
                $ylabel = $value['ylabel'] ?? '';
                
                if ($type === 'bar' && $barChart) {
                    echo $barChart->render([
                        'data' => $this->customGraph_results[$key] ?? [],
                        'title' => $title,
                        'graphId' => "customgraph_$key",
                        'limit' => 10
                    ]);
                } elseif ($type === 'vbar' && $vbarChart) {
                    echo $vbarChart->render([
                        'data' => $this->customGraph_results[$key] ?? [],
                        'title' => $title,
                        'graphId' => "customgraph_$key",
                        'limit' => 10
                    ]);
                } elseif ($type === 'line' && $lineChart) {
                    echo $lineChart->render([
                        'data' => $this->customGraph_results[$key] ?? [],
                        'title' => $title,
                        'graphId' => "customgraph_$key",
                        'limit' => 10,
                        'xlabel' => $xlabel,
                        'ylabel' => $ylabel
                    ]);
                } else {
                    // Default to pie chart
                    echo $pieChart->render([
                        'data' => $this->customGraph_results[$key] ?? [],
                        'title' => $title,
                        'graphId' => "customgraph_$key",
                        'limit' => 10
                    ]);
                }
            }
        } 

        print "</div>";
    }
}
