<?php

namespace Opensitez\Plugins;

class OSZContent extends DBLayer
{

    function fetch_data($app)
    {
        $app['fields'] = ["id", "node_type", "status", "body", "slug", "parent", "title", "created", "updated"];
        $results = $this->query_nodes($app);

        return $results;
    }
}
