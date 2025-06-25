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
    private $config_object;
    private $defaultcolors=array("darkblue","black","blue","green","darkgreen","darkred","red","orange");
    private $logformats = [
        "common" => [
            "name" => "Apache Common Log Format",
            "format" => "%h %l %u %t \"%r\" %>s %b"
        ],
        "combined" => [
            "name" => "Apache Combined Log Format",
            "format" => "%h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\""
        ],

        "vhost_combined" => [
            "name" => "Apache Virtual Host Combined Log Format",
            "format" => "%{Host}i:%p %h %l %u %t \"%r\" %>s %O \"%{Referer}i\" %v \"%{User-Agent}i\""
        ],
        "error" => [
            "name" => "Apache Error Log Format",
            "format" => "[%x] [%z] [client %a] %m"
        ],
    ];
    private $complexFields = [
        '%h' => ['fields' => ['ip']],
        '%l' => ['fields' => ['identity']],
        '%u' => ['fields' => ['user']],
        '%t' => ['fields' => ['time', 'timezone']],
        '%r' => ['fields' => ['verb', 'path', 'protocol']],
        '%>s' => ['fields' => ['status_code']],
        '%b' => ['fields' => ['response_size']],
        '%{Referer}i' => ['fields' => ['referer']],
        '%{User-Agent}i' => ['fields' => ['user_agent']],
        "%{Host}i:%p" => ['fields' => ['vhost']],
        '%{Host}i' => ['fields' => ['host']],
        '%p' => ['fields' => ['port']],
        '%O' => ['fields' => ['bytes_sent']],
        '%v' => ['fields' => ['virtual_host']],
        '%a' => ['fields' => ['client_ip']],
        '%m' => ['fields' => ['request_method']],
        '%x' => ['fields' => ['error_time']],
        '%z' => ['fields' => ['error_type']],
        '%i' => ['fields' => ['pid']],
    ];
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
    }   
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
        $this->ruleStats="";
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
    function showCustomGraphs()
    {
        print "<style>
        .custom-graphs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); /* Responsive columns */
            gap: 20px; /* Space between graphs */
            padding: 10px; /* Padding around the grid */
        }
        .custom-graph {
            border: 1px solid #ccc;
            padding: 15px;
            background-color: transparent; /* Remove background */
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Add shadow for better appearance */
            transition: transform 0.2s, box-shadow 0.2s; /* Smooth hover effect */
        }
        .custom-graph:hover {
            transform: scale(1.05); /* Slight zoom on hover */
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.15); /* Enhanced shadow on hover */
        }
        .custom-graph h3 {
            margin: 0 0 10px 0;
            font-size: 18px;
            color: #333; /* Darker text for better readability */
        }
        @media (max-width: 900px) {
            .custom-graphs-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* Fewer columns on smaller screens */
            }
        }
        @media (max-width: 600px) {
            .custom-graphs-grid {
                grid-template-columns: 1fr; /* Single column for very small screens */
            }
            .custom-graph {
                padding: 10px; /* Adjust padding for smaller screens */
            }
        }
    </style>";

        print "<div class='custom-graphs-grid'>"; // Start grid container

        foreach ($this->customGraphs as $key => $value) {
            print "<div class='custom-graph' id='customgraph_$key'>";
            print "<h3>" . htmlentities($value['label']) . "</h3>";
            $this->showCustomGraph($key);
            print "</div>";
        }

        print "</div>"; 
    }
    function showCustomGraph($graph_name)
    {
        $max = 10; // Limit to top 10 labels
        if (isset($this->customGraph_results[trim($graph_name)])) {
            arsort($this->customGraph_results[$graph_name]);
            if (count($this->customGraph_results[$graph_name]) > $max) {
                $top_labels = array_slice($this->customGraph_results[$graph_name], 0, $max, true);
                $others = array_slice($this->customGraph_results[$graph_name], $max, null, true);
                $others_total = array_sum($others);
                $top_labels["Others"] = $others_total; // Aggregate remaining values under "Others"
            } else {
                $top_labels = $this->customGraph_results[trim($graph_name)];
            }

            $label = urlencode(implode("*", array_keys($top_labels)));
            $data = implode("*", array_values($top_labels));
            $title = $this->customGraphs[$graph_name]['label'] ?? "Custom Graph"; // Use the label from customGraphs
            $this->d3pie("customgraph_$graph_name", $top_labels, $label, $title); // Pass limited labels to d3pie
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
            $dataList .= "<div style=\"display:block\"><span style='font-weight:bold;color:#e1b698;text-shadow: 2px 1px black;display:inline-block;'>" . htmlentities($key) . "</span> : &nbsp; <span style='color:#aaa;'>$value</span></div>\n";
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
        $template = new \Opensitez\Simplicity\SimpleTemplate();
        $templatePath = __DIR__ . '/templates/log.tpl';
        $template->setFile($templatePath);
        
        $colorCycle = $this->config_object->get('colorCycle') ?? $this->defaultcolors;
        $output = '';
        $coloredLine = "";
        $idx= 0;
        foreach($this->columns as $col_id=>$col_name)
        {
                    $idx++;
                    $color = $colorCycle[$idx % count($colorCycle)];
                    $coloredLine .= "<font color=" . $color . ">" . htmlentities(trim($col_name)) . "</font>&nbsp;\t\n";
        }
        $output .= $coloredLine . "<br/>";
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
    }    
    function splitCustomLine($line,$logformat="vhost_common") {
        $logformatsToTry = [$logformat] + array_keys($this->logformats); // Include the provided format and all available formats
        $complexFields = $this->complexFields;

        foreach ($logformatsToTry as $format) {
            $missing = [];
            $formatString = $this->logformats[$format]['format'] ?? null;

            if (!$formatString) {
                continue; // Skip if format is not defined
            }

            // Step 1: Split the log line into fields using splitWhitespaceLine logic
            $lineFields = $this->splitWhitespaceLine($line);

            // Step 2: Parse the format string into fields, treating quoted and bracketed expressions as single units
            $formatFields = [];
            $currentField = '';
            $insideQuotes = false;
            $insideBrackets = false; // Track if inside brackets

            foreach (str_split($formatString) as $char) {
                if ($char === '"') {
                    $insideQuotes = !$insideQuotes;
                    $currentField .= $char;
                    if (!$insideQuotes) {
                        $formatFields[] = trim($currentField, '"');
                        $currentField = '';
                    }
                } elseif ($char === '[') {
                    $insideBrackets = true; 
                    $currentField .= $char;
                } elseif ($char === ']') {
                    $insideBrackets = false;
                    $currentField .= $char;
                    if ($currentField !== '') {
                        $formatFields[] = trim($currentField, '[]');
                        $currentField = '';
                    }
                } elseif ($insideQuotes||$insideBrackets) {
                    $currentField .= $char;
                } elseif ($char === ' ') {
                    if ($currentField !== '') {
                        $formatFields[] = $currentField;
                        $currentField = '';
                    }
                } else {
                    $currentField .= $char;
                }
            }
            if ($currentField !== '') {
                $formatFields[] = $currentField; 
            }
            // Step 3: Handle complex fields using the lookup array
            $processedLineFields = [];
            $mappedFields = [];
            $fieldIndex = 0;
            foreach ($formatFields as $formatField) {
                $details = $complexFields[$formatField] ?? null;

                if ($details) {
                    if(count($details['fields']) > 1) {

                        $parts = explode(' ', $lineFields[$fieldIndex] ?? '', 3); // Split into method, path, and protocol
                        foreach ($details['fields'] as $index => $friendlyName) {
                            if($friendlyName=="time") {
                                
                                if(count($parts)>1) {
                                    if(strstr($parts[0],"/")) {
                                    $timeparts = explode(':', $parts[0]);
                                    $day=$timeparts[0];
                                    $hour=$timeparts[1];
                                    $clock="{$timeparts[1]}:{$timeparts[2]}";

                                    } else {
                                        $day="-";
                                        $hour="-";
                                        $clock="-";
                                    }
                                } 
                            }
                            if(isset($parts[$index])) {
                                $mappedFields[$friendlyName] = $parts[$index];
                            } else {
                                $mappedFields[$friendlyName] = '<span style="color: red;">Missing</span>';
                            $missing[]=$friendlyName;
                            }
                        }
                        $fieldIndex++;
                    } else {
                        $mappedFields[$details['fields'][0]] = $lineFields[$fieldIndex] ?? '<span style="color: red;">Missing</span>';
                        $fieldIndex++;
                    }

                } else {
                    $processedLineFields[] = $lineFields[$fieldIndex] ?? '<span style="color: red;">Missing</span>';
                    $fieldIndex++;
                }
            }

            if(count($missing) < 1) {
                $mappedFields['type'] = $format; 
                $mappedFields['day']=$day;
                $mappedFields['hour']=$hour;
                $mappedFields['clock']=$clock;               
                return $mappedFields;
            }
        } 
    } 
    function splitWhitespaceLine($line)
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
            $label=urlencode(implode("*",array_keys($this->engine_stats)));
            $data=implode("*",array_values($this->engine_stats));
            $numgraph +=1;
            $this->d3pie("graph".$numgraph,$this->engine_stats,$label,true);
            if(is_array($this->ruleStats))
            {

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

        $logformat = $this->config_object->get('logformat') ?? "common";
        $logformat = $this->config_object->get('logformat')??"combined";

        if($log_lines) {
            $first = $this->splitCustomLine($log_lines[0]);
            $this->columns = array_keys($first);
        }
        foreach($log_lines as $line)
        {
            if(trim($line<>""))
            {
                if( $logformat=="space" )
                {
                    $logline = $this->splitWhitespaceLine($line);

                } elseif ($logformat=="combined" || $logformat="vhost_combined"|| $logformat == "common")
                {
                    $logline = $this->splitCustomLine($line,$logformat);
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
                    $logline=$this->splitTabbedLine($line);
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
    function generateRegexFromLogFormat($logFormat) {
        $placeholderPatterns = [
            '/%{[^}]+}i/' => '"([^"]*?)"', // Headers like %{Host}i
            '/%p/' => '(\d+)',             // Port
            '/%h/' => '([^\s]+)',          // IP Address
            '/%l/' => '([^\s]+)',          // Identity
            '/%u/' => '([^\s]+)',          // User
            '/%t/' => '\[(.*?)\]',         // Timestamp
            '/%r/' => '"([^"]*?)"',        // Request
            '/%>s/' => '(\d+)',            // Status Code
            '/%O/' => '(\d+)',             // Bytes Sent
            '/%b/' => '(\d+|-)',           // Response Size (fixed to handle "-" for empty size)
            '/%v/' => '([^\s]+)',          // Virtual Host
            '/%a/' => '([^\s]+)',          // Client IP Address
            '/%m/' => '([A-Z]+)',          // Request Method
        ];

        if (isset($this->logformats[$logFormat])) {
            $logFormat = $this->logformats[$logFormat]['format'];
        }

        // Escape square brackets in the format string
        $logFormat = str_replace('[', '\[', $logFormat);
        $logFormat = str_replace(']', '\]', $logFormat);

        // Replace placeholders with regex patterns
        foreach ($placeholderPatterns as $placeholder => $pattern) {
            $logFormat = preg_replace($placeholder, $pattern, $logFormat);
        }

        print "<li>Generated Regex: $logFormat</li>"; // Debug code restored

        return '/^' . $logFormat . '$/';
    }
    function renderGraph($graphId, $data, $title)
    {
        $max = 10; // Limit to top 10 labels
        arsort($data);
        if (count($data) > $max) {
            $top_labels = array_slice($data, 0, $max, true);
            $others = array_slice($data, $max, null, true);
            $others_total = array_sum($others);
            $top_labels["Others"] = $others_total; // Aggregate remaining values under "Others"
        } else {
            $top_labels = $data;
        }

        $label = urlencode(implode("*", array_keys($top_labels)));
        $data_values = implode("*", array_values($top_labels));

        // Print the graph container with title and graph content
        print "<div class='graph'>";
        print "<div class='graph-content'>";
        print "<h3 class='graph-title'>" . htmlentities($title) . "</h3>";
        $this->d3pie($graphId, $top_labels, $label, $title); // Render the graph
        print "</div>";
        print "</div>";
    }
    function showAllGraphs()
    {
        print "<style>
        .graphs-container {
            display: flex; /* Use flexbox to arrange graphs side by side */
            flex-wrap: wrap; /* Allow graphs to wrap to the next row if needed */
            gap: 7px; /* Space between graphs */
            justify-content: center; /* Center graphs horizontally */
        }
        .graph {
            width: 300px; /* Fixed width for each graph on larger screens */
            height: auto; /* Adjust height based on content */
            border: 1px solid #ccc; /* Add border for better separation */
            border-radius: 8px; /* Rounded corners for a modern look */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
            padding: 7px; /* Add padding inside the graph container */
            text-align: center; /* Center content inside each graph */
        }
        .graph-canvas {
            width: 100%; /* Make the canvas take full width */
            height: auto; /* Adjust height automatically */
        }
        @media (max-width: 768px) {
            .graph {
                width: 100%; /* Full width on smaller screens */
                margin: 0; /* Remove any margin */
            }
        }
    </style>";

        print "<div class='graphs-container'>"; // Unified container for all graphs

        // Show standard graphs
        $numgraph = 0;
        if (isset($this->filteredLog) && strlen($this->filteredLog) > 0) {
            $numgraph++;
            $this->renderGraph("graph" . $numgraph, $this->engine_stats, "Engine Stats");

            if (is_array($this->ruleStats)) {
                arsort($this->ruleStats);
                $this->renderGraph("tenarray", $this->ruleStats, "Rule Stats");
            }
        }
        foreach ($this->customGraphs as $key => $value) {
            $title = $value['label'] ?? "Custom Graph";
            $this->renderGraph("customgraph_$key", $this->customGraph_results[$key] ?? [], $title);
        }

        print "</div>"; // Close unified container
    }
}
