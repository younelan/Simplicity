<?php
namespace Opensitez\Simplicity;

class RuleEngine extends Base
{
    private $rules = [];
    private $families = [];
    private $engine_file = '';
    private $engine_rules = [];
    private $engine_families = [];
    private $filters = [];

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

        return $retval;
    }
    
    function init()
    {
        $this->filters = [];
        $this->engine_file = $this->config_object->get('paths.engine_file');
        if ($this->engine_file) {
            $this->loadEngineRules();
        }
    }
    
    function setFilter($filter_field,$filter_type,$filter_value)
    {
        $this->rules["filters"][]=array("field"=>$filter_field,"type"=>$filter_type,
            "value"=>$filter_value,"name"=>"$filter_field $filter_type $filter_value","class"=>"filters");
    }

    function loadEngineRules($category='engines')
    {
        $filename = $this->config_object->get('paths.engine_file');
        if (file_exists($filename)) {
            $this->parseRules($filename, $category);
        } else {
            throw new \Exception("Engine rules file not found: " . $this->engine_file);
        }
    }
    
    function parseRules($filename, $category='engines')
    {
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
    
    function match_rule( $log_entry )
    {
        $retval=false;
        $rules = $this->config_object->get('engines', []);
        
        foreach($rules as $category=>$ruleset)
        {
            if (is_array($ruleset)) {
                foreach($ruleset as $rule_id=>$rule)
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
        }

        return false;
    }

    function getEngineRules()
    {
        return $this->engine_rules;
    }
}