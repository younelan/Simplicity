<?php
namespace Opensitez\Simplicity\Components;
use Symfony\Component\Yaml\Yaml;

class LogHelper extends \Opensitez\Simplicity\Component
{
    private $filters;
    private $default_stats=['Visitors'=>0,'Scan'=>0,'Engines'=>0,'Tool'=>0];
    private $parsedLog;
    private $filteredLog;
    private $columns;
    private $customGraphs;
    private $results;
    private $rule_engine;
    private $defaultcolors=array("darkblue","black","blue","green","darkgreen","darkred","red","orange");

    function init()
    {
        $this->parsedLog="";
        $this->results=[
            'engine_stats' => ["label"=>"Engine Stats","type"=>"pie","data"=>$this->default_stats],
            'rule_stats' => ["label"=>"Rule Stats", "type"=>"bar", "data"=>[]]
        ];
        if (!isset($this->columns)) {
            $this->setDefaultColumns();
        }
        $this->rule_engine = new \Opensitez\Simplicity\RuleEngine($this->config_object);
        $this->rule_engine->init();
    }
    function loadRules($filename, $category = 'engines')
    {
        $this->rule_engine->loadEngineRules($category);
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
        } 
        else // Tab-separated format
        {
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
    function setCustomGraphs($customGraphs)
    {
        $this->customGraphs=$customGraphs;
    }
    function setColumns($columnNames)
    {
        $this->columns=$columnNames;
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
        $logParser = $this->get_component('apachelogline');
        if (!$logParser) {
            $logParser = new \Opensitez\Simplicity\Components\ApacheLogLine($this->config_object);
        }

        if($log_lines) {
            $first = $logParser->splitCustomLine($log_lines[0]);
            $this->columns = array_keys($first);
        }
        foreach($log_lines as $line)
        {
            $coloredLine = "";
            $curcolor=0;
            $idx=0;            
            if(trim($line) != "")
            {
                if( $logformat=="space" )
                {
                    $logline = $logParser->splitWhitespaceLine($line);
                } 
                elseif ($logformat=="combined" || $logformat=="vhost_combined"|| $logformat == "common")
                {
                    $logline = $logParser->splitCustomLine($line,$logformat);
                    $entry = $logline;

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

                $rule = $this->rule_engine->match_rule($entry);
                if($rule===false) {
                    $isEngine=false;
                    $this->results['engine_stats']['data']['Visitors']++;
                } else {
                    $newval = $this->results['engine_stats']['data'][ucfirst($rule['category'])] ?? 0;
                    $this->results['engine_stats']['data'][ucfirst($rule['category'])] = $newval + 1;
                    $isEngine=true;
                }

                if($isEngine===false)
                {
                    $this->filteredLog = $coloredLine . "<br>" . $this->filteredLog;
                    @$this->results['engine_stats']['Visitors']++;
                }
                else
                {
                    @$this->results['engine_stats'][ucfirst($rule['category'])]++;
                    $rule_name=$rule['name'];
                    $newstat = $this->results['rule_stats']['data'][trim($rule_name)]??0;
                    $this->results['rule_stats']['data'][trim($rule_name)] = $newstat+1;
                }
                $this->processCustomGraphs($entry);
            }
        }
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
