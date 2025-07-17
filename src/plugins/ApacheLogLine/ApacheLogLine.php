<?php

namespace Opensitez\Simplicity\Plugins;

use Opensitez\Simplicity\MSG;

class ApacheLogLine extends \Opensitez\Simplicity\Plugin
{
    public $name = "Apache Log Line Parser";
    public $description = "Parses Apache log lines in various formats";
    
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

    function on_event($event)
    {
        if ($event['type'] === MSG::onComponentLoad) {
            // $this->framework->register_type('blocktype', 'apachelogline');
            // $this->framework->register_type('contentprovider', 'apachelogline');
        }
        parent::on_event($event);
    }

    function splitTabbedLine($line)
    {
        return explode("\t", $line);
    }    

    function splitCustomLine($line, $logformat = "vhost_common") 
    {
        $logformatsToTry = [$logformat] + array_keys($this->logformats);
        $complexFields = $this->complexFields;

        foreach ($logformatsToTry as $format) {
            $missing = [];
            $formatString = $this->logformats[$format]['format'] ?? null;

            if (!$formatString) {
                continue;
            }

            $lineFields = $this->splitWhitespaceLine($line);

            $formatFields = [];
            $currentField = '';
            $insideQuotes = false;
            $insideBrackets = false;

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

            $processedLineFields = [];
            $mappedFields = [];
            $fieldIndex = 0;
            foreach ($formatFields as $formatField) {
                $details = $complexFields[$formatField] ?? null;

                if ($details) {

                    if(count($details['fields']) > 1) {
                        $parts = explode(' ', $lineFields[$fieldIndex] ?? '', 3);
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
                            $hostname = '';

                            if(isset($parts[$index])) {
                                $mappedFields[$friendlyName] = $parts[$index];
                            } else {
                                $mappedFields[$friendlyName] = '<span style="color: red;">Missing</span>';
                                $missing[]=$friendlyName;
                            }
                        }
                        $fieldIndex++;
                    } else {
                        //print "<pre>\n";
                        $friendlyName = $details['fields'][0];
                        if($friendlyName=="vhost") {
                            // print "Processing vhost field: $friendlyName\n<br/>";
                            // print_r( $lineFields[$fieldIndex]);
                            $vhostParts = explode(':', $lineFields[$fieldIndex]);
                            if(count($vhostParts)>1) {
                                //$parts[0] = trim($parts[0],"www.");

                                $hostname = $vhostParts[0] ?? '<span style="color: red;">Missing</span>';
                                $port = $vhostParts[1] ?? '<span style="color: red;">Missing</span>';
                            } else {
                                $hostname = $parts[0] ?? "";
                                $port = "-";

                            }
                        $mappedFields["host"] = (strpos($hostname, 'www.') === 0) ? substr($hostname, 4) : $hostname;
                        $mappedFields["port"] = $port;

                        }
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
            while ($position < $length && $line[$position] === ' ') {
                $position++;
            }
            
            if ($position >= $length) break;
            
            $value = '';
            
            if ($line[$position] === '[') {
                $position++;
                while ($position < $length && $line[$position] !== ']') {
                    $value .= $line[$position];
                    $position++;
                }
                if ($position < $length) $position++;
            } elseif ($line[$position] === '"') {
                $position++;
                while ($position < $length && $line[$position] !== '"') {
                    if ($line[$position] === '\\' && $position + 1 < $length) {
                        $position++;
                    }
                    $value .= $line[$position];
                    $position++;
                }
                if ($position < $length) $position++;
            } else {
                while ($position < $length && $line[$position] !== ' ') {
                    $value .= $line[$position];
                    $position++;
                }
            }
            $fields[] = $value;
        }
        
        return $fields;
    }

    public function isAvailable()
    {
        return true;
    }
}
