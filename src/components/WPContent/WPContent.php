<?php

namespace Opensitez\Simplicity\Components;

use Opensitez\Simplicity\MSG;

class WPContent extends \Opensitez\Simplicity\DBLayer
{
    public $name = "WPContent";
    public $description = "WordPress Content Provider";

    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::onComponentLoad:
                // Register this component as a content provider for WordPress
                $this->framework->register_type('contentprovider', 'wordpress');
                break;
        }
        return parent::on_event($event);
    }
    function listNodeTypes($app)
    {
        $sql="SELECT ID, post_title AS group_name
         FROM wp_posts
         WHERE post_type = 'acf-field-group'
           AND post_status = 'publish';";
        $dbprefix = $app['dbprefix'] ?? "";
        $database = $app['database'] ?? "";
        $post_type = $app['post-type'] ?? ["page", "post"];
        $post_status = $app['post-status'] ?? ["publish"];
        // $query = [
        //     "dbprefix" => $dbprefix,
        //     "table" => "wp_posts p",
        //     "fields" => ["p.id as id ", "p.post_type as node_type", "p.post_status as status", "post_content as body", "p.post_name as slug", "p.post_parent as parent", "p.post_title as title", "p.post_date as created_at", "p.post_modified as last_updated"],
        //     "where" => [
        //         ["field" => "p.post_name", "value" => "$querystr"],
        //         ["type  " => "AND", "field" => "post_status", "value" => $post_status],
        //         ["type" => "AND", "field" => "post_type", "value" => $post_type],
        //     ],
        //     "limit" => "15",
        //     "orderby" => "post_modified DESC"
        // ];        
        
        $query = "SELECT
            f.ID,
            f.post_parent as parent,
            f.post_type as post_type,
            parent.post_title node_type,
            f.post_title AS field_label,
            f.post_name AS field_name,
            m1.meta_value AS field_key,
            m2.meta_value AS field_type
            FROM wp_posts f
            LEFT JOIN wp_posts parent ON parent.ID=f.post_parent
            LEFT JOIN wp_postmeta m1 ON f.ID = m1.post_id AND m1.meta_key = 'field_key'
            LEFT JOIN wp_postmeta m2 ON f.ID = m2.post_id AND m2.meta_key = 'type'
            WHERE f.post_type = 'acf-field'

            ORDER BY f.menu_order;";


    }
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
