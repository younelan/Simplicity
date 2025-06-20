<?php

namespace Opensitez\Simplicity\Plugins;

use Opensitez\Simplicity\MSG;

class OSZContent extends \Opensitez\Simplicity\DBLayer
{
    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::PluginLoad:
                // Register this plugin as a content provider for OpenSite
                $this->plugins->register_type('contentprovider', 'opensite');
                break;
        }
        return parent::on_event($event);
    }

    function fetch_data($app)
    {
        $app['fields'] = ["id", "node_type", "status", "body", "slug", "parent", "title", "created", "updated"];
        $results = $this->query_nodes($app);

        return $results;
    }
}
