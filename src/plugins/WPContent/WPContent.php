<?php

namespace Opensitez\Plugins;

class WPContent extends DBLayer
{

    function fetch_data($app)
    {
        $dbprefix = $app['dbprefix'] ?? "";
        $database = $app['database'] ?? "";
        $post_type = $app['post-type'] ?? ["page", "post"];
        $post_status = $app['post-status'] ?? ["publish"];
        if (!is_array($post_type))
            $post_type = $post_type;
        if (!is_array($post_status))
            $post_type = $post_status;
        //print_r($post_type);exit;
        $sql = "SELECT p1.*, wm2.meta_value FROM wp_posts p1 
            LEFT JOIN wp_postmeta wm1 ON ( wm1.post_id = p1.ID AND wm1.meta_value IS NOT NULL AND wm1.meta_key = '_thumbnail_id' ) 
            LEFT JOIN wp_postmeta wm2 ON ( wm1.meta_value = wm2.post_id AND wm2.meta_key = '_wp_attached_file' AND wm2.meta_value IS NOT NULL ) 
            WHERE p1.post_status='publish' AND p1.post_type='post' ORDER BY p1.post_date DESC Limit 5;  ";

        $querystr = $app['path'] ?? "";

        $query = [
            "dbprefix" => $dbprefix,
            "table" => "wp_posts p",
            "fields" => ["p.id as id ", "p.post_type as node_type", "p.post_status as status", "post_content as body", "p.post_name as slug", "p.post_parent as parent", "p.post_title as title", "p.post_date as created_at", "p.post_modified as last_updated"],
            "where" => [
                ["field" => "p.post_name", "value" => "$querystr"],
                ["type" => "AND", "field" => "post_status", "value" => $post_status],
                ["type" => "AND", "field" => "post_type", "value" => $post_type],
            ],
            "limit" => "15",
            "orderby" => "post_modified DESC"
        ];
        if (!$querystr) {
            $query['where'] = [
                ["type" => "AND", "field" => "post_status", "value" => $post_status],
                ["type" => "AND", "field" => "post_type", "value" => $post_type]
            ];
            // $query['where']=[["type"=>"AND","field"=>"post_type","value"=>["page",]] ];
        }
        if ($database)
            $query['database'] = $database;

        $results = $this->get_data($query);
        //print_r($results);exit;
        return $results;
    }
}
/*                ["type"=>"left", "from"=>"","to"=>""],*/
