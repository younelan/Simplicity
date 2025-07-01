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
        $this->results=[
            'engine_stats' => ["label"=>"Engine Stats","type"=>"pie","data"=>$this->default_stats],
            'rule_stats' => ["label"=>"Rule Stats", "type"=>"bar", "data"=>[]]
        ];

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

    function processCustomGraphs($entry) {
        foreach ($this->customGraphs ?? [] as $graph_name => $graph_rule) {
            $type = $graph_rule['type'] ?? 'pie';
            $x_field = $graph_rule['x'] ?? null;
            $y_field = $graph_rule['y'] ?? null;

            if (!isset($this->results[$graph_name])) {
                $this->results[$graph_name] = [
                    'type' => $type,
                    'title' => $graph_rule['label'] ?? 'Custom Graph',
                    'label' => $graph_rule['label'] ?? 'Custom Graph',
                    'data' => [],
                    'xlabel' => $graph_rule['xlabel'] ?? '',
                    'ylabel' => $graph_rule['ylabel'] ?? '',
                    'xlabels' => [],
                    'ylabels' => []
                ];
            }

            if ($type === 'line') {
                // Handle matrix data (x => [y => count])
                $field = $graph_rule['x'] ?? null;
                //print "Processing line chart data for $graph_name $field\n";
                if ($field && isset($entry[$field])) {
                    $graph_value = trim($entry[$field]);
                    if (isset($this->results[$graph_name]['data'][$graph_value])) {
                        $this->results[$graph_name]['data'][$graph_value]++;
                    } else {
                        $this->results[$graph_name]['data'][$graph_value] = 1;
                    }
                }
            } else {
                // Handle single field data (non-line charts)
                $field = $graph_rule['field'] ?? null;
                if ($field && isset($entry[$field])) {
                    $graph_value = trim($entry[$field]);
                    if (isset($this->results[$graph_name]['data'][$graph_value])) {
                        $this->results[$graph_name]['data'][$graph_value]++;
                    } else {
                        $this->results[$graph_name]['data'][$graph_value] = 1;
                    }
                }
            }

            // Sort data by count in descending order
            arsort($this->results[$graph_name]['data']);
        }
    }
    function parseLog()
    {

        $filenames = $this->config_object->get('file');
        if (is_array($filenames)) {
            foreach ($filenames as $filename) {
                $this->parseLogFile($filename);
            }
        } else {
            $this->parseLogFile($filenames);
        }
    }

    function parseLogFile($filename)
    {
        if (!$filename) {
            print("Log file not specified in configuration.\n<br/>");
            return false;
        }
        if ( !file_exists($filename)) {
            print("Log file " . $filename . " does not exist or is inaccessible.\n<br/>");
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
        $logParser = $this->get_component('apachelogline');
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

                $rule = $this->match_rule($entry);
                if($rule===false) {
                    $isEngine=false;
                    $this->results['engine_stats']['data']['Visitors']++;
                } else {
                    $newval = $this->results['engine_stats']['data'][ucfirst($rule['category'])] ?? 0;
                    $this->results['engine_stats']['data'][ucfirst($rule['category'])] = $newval + 1;
                    $isEngine=true;

                }

                // if(isset($this->rules["filters"]) && $isFilter===false)
                // {
                //     $this->filter_count++;
                // }

                if($isEngine===false)
                {
                    $this->filteredLog = $coloredLine . "<br>" . $this->filteredLog;
                    $this->total_visitors++;
                    @$this->results['engine_stats']['Visitors']++;
                }
                else
                {
                    @$this->results['engine_stats'][ucfirst($rule['category'])]++;
                    $this->total_engines ++;
                    $rule_name=$rule['name'];
                    $newstat = $this->results['rule_stats']['data'][trim($rule_name)]??0;
                    $this->results['rule_stats']['data'][trim($rule_name)] = $newstat+1;

                }
                $this->processCustomGraphs($entry);

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
    function get($var) {
        switch($var) {
            case 'css':
                $style = __DIR__ . '/../../templates/graphs.css';
                if (file_exists($style)) {
                    $style = file_get_contents($style);
                }
                else { die("Graphs CSS file not found: $style"); }
                return "<style>\n$style\n</style>";

            case 'results':
                return $this->results;
            case "filtered_log":
                return $this->filteredLog;
            case 'columns':
                return $this->columns;
            case 'graphs':
                return $this->customGraphs;
            default:
                return $this->config_object->get($var);
        }


    }
    function getResults()
    {
        return $this->results;
    }


}
