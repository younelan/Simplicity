<?php

namespace Opensitez\Simplicity\Plugins;

use Opensitez\Simplicity\MSG;

class DrupalContent extends \Opensitez\Simplicity\DBLayer
{
    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::PluginLoad:
                $this->framework->register_type('contentprovider', 'drupal');
                break;
        }
        return parent::on_event($event);
    }
    
    function fetch_data($app)
    {
        $dbprefix = $app['dbprefix'] ?? "";
        $database = $app['database'] ?? "";
        //print_r($app);exit;
        // $myquery="SELECT n.nid,n.title,n.type, b.body_value, a. FROM $database.$dbprefixnode n 
        // left join $database.$dbprefixfield_data_body b on n.nid = b.entity_id
        // left join $database.$dbprefixurl_alias a on a.source=concat('node/',node.nid)";
        //SELECT info FROM system WHERE type = 'module' AND name = 'node';
        $query10 = [
            "dbprefix" => $dbprefix,
            "table" => "config",
            "fields" => ["name"],
            "limit" => 1
        ];
        $query7 = [
            "dbprefix" => $dbprefix,
            "table" => "system",
            "fields" => ["name"],
            "limit" => 1
        ];
        $version = 0;
        $querystr = $app['path'] ?? "";
        if (isset($app['database'])) {
            $query10["database"] = $app['database'];
            $query7["database"] = $app['database'];
        }
        try {
            $this->get_data($query10);
            $version = 10;
        } catch (\Exception $e) {
            $version = 0;
        }
        if(!$version) {
            try {
                $this->get_data($query7);
                $version = 7;
            } catch (\Exception $e) {
                $version = 0;
            }
        }

        
        if ($version === 10) {
            $body_table = "node__body b";
            $alias_table = "path_alias";
            $alias_source = "a.path";
            $query = [
                "dbprefix" => $dbprefix,
                "table" => "node n",
                "fields" => ["n.nid as id ", "n.type as node_type", "b.body_value as body", "a.alias as slug", "a.path", "d.title", "d.created", "d.changed"],
                "where" => [
                    ["field" => "alias", "value" => "/$querystr"],
                    ["type" => "OR", "field" => "a.path", "value" => "/$querystr"],
                ],
                "joins" => [
                    ["type" => "left", "table" => "node__body b", "from" => "n.nid", "to" => "b.entity_id"],
                    ["type" => "left", "table" => "node_field_data d", "from" => "n.nid", "to" => "d.nid"],
                    ["type" => "left", "table" => "$alias_table a", "from" => "$alias_source", "to" => "concat('/node/',n.nid)"],
                ],
                "limit" => "65",
                "orderby" => "d.changed DESC,d.created DESC"
            ];
        } elseif ($version === 7) {
            if ($version == 10) {
                $querystr = "$querystr";
            }
            $body_table = "field_data_body b";
            $alias_table = "url_alias";
            $alias_source = "a.source";
            $query = [
                "dbprefix" => $dbprefix,
                "table" => "node n",
                "fields" => ["n.nid as id ", "n.type as node_type", "b.body_value as body", "a.alias as slug", "$alias_source", "n.title", "n.created", "n.changed"],
                "where" => [
                    ["field" => "alias", "value" => "$querystr"],
                    ["type" => "OR", "field" => "source", "value" => "$querystr"],
                ],
                "joins" => [
                    ["type" => "left", "table" => $body_table, "from" => "n.nid", "to" => "b.entity_id"],
                    ["type" => "left", "table" => "$alias_table a", "from" => "$alias_source", "to" => "concat('node/',n.nid)"],
                ],
                "limit" => "65",
                "orderby" => "n.changed DESC,n.created DESC"
            ];
        } elseif ($version < 7) {
            if ($version == 10) {
                $querystr = "$querystr";
            }
            $body_table = "field_data_body b";
            $alias_table = "url_alias";
            $alias_source = "a.source";
            $query = [
                "dbprefix" => $dbprefix,
                "table" => "node n",
                "fields" => ["n.nid as id ", "n.type as node_type", "b.body_value as body", "a.alias as slug", "$alias_source", "n.title", "n.created", "n.changed"],
                "where" => [
                    ["field" => "alias", "value" => "$querystr"],
                    ["type" => "OR", "field" => "source", "value" => "$querystr"],
                ],
                "joins" => [
                    ["type" => "left", "table" => $body_table, "from" => "n.nid", "to" => "b.entity_id"],
                    ["type" => "left", "table" => "$alias_table a", "from" => "$alias_source", "to" => "concat('node/',n.nid)"],
                ],
                "limit" => "65",
                "orderby" => "n.changed DESC,n.created DESC"
            ];
        } 

        //print_r($results);

        //print_r($query);
        if (isset($app['database'])) {
            $query["database"] = $app['database'];
        }
        if (!$querystr) {
            $query['where'] = [];
        }
        if ($database)
            $query['database'] = $database;

        $results = $this->get_data($query);

        // Return results as-is, let ContentProvider handle URL building
        return $results;
        
    }
}
/*                ["type"=>"left", "from"=>"","to"=>""],*/
