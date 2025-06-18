<?php

namespace Opensitez\Simplicity;

use \PDO;

class DBLayer extends \Opensitez\Simplicity\Plugin
{
    protected $config = [];
    protected $connection = null;
    protected $db;
    protected $node_types;
    protected $stmt;
    protected $dbconfig;
    private $debug_style = '<style>
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px;}
        th, td { padding: 8px 12px; border: 1px solid #cdbcbc; text-align: left; }
        th { background-color: #4f4837; color: white; }
        tr:nth-child(odd) { background-color: #e5e0d3; }
        .firstcol { color: #c96666; font-weight: bold; }
        tr:nth-child(even) { background-color: #fffdf3; }
        .id-cell { font-weight: bold; color: #ff0000; }
        .tabledetails  {text-align: center; background-color:  #4f4837;color: white}
      </style>';
    protected $osz_fields = [
        "tbl_nodes" => "sites__node_items",
        "tbl_tree" => "sites__node_tree",
        "tbl_node_types" => "sites__node_types",
        "tbl_users" => "users",
        "tbl_sites" => "sites_list",
        "tbl_users" => "users",
        "node_group" => "content_group",
        "node_slug" => "slug",
    ];

    public function connect()
    {
        $globals = $this->config_object->get('defaults');
        $connections = $this->config_object->get('connections');
        $current_site = $this->config_object->get('site');
        $connection_name=$this->config_object->get('site.db');
        $this->dbconfig = $connections[$connection_name] ?? [];
        //print_r($this->dbconfig);exit;
        //$current_site = $this->config_object->getCurrentSite();
        //$app = $this->config_object->getApp();
        //$connection_name = $globals['defaults']['db'];
        //$this->dbconfig = $globals['connections'][$connection_name];
        if ($this->dbconfig['driver'] ?? "" == "mysql") {
            $this->connection = new PDO("mysql:host=" . $this->dbconfig['host'] . ";dbname=" .
                $this->dbconfig['db'] . ";charset=utf8", $this->dbconfig['user'], $this->dbconfig['password']);
        } elseif ($this->dbconfig['driver'] == "postgres" || ($this->dbconfig['driver'] ?? "") == "pgsql") {
            $this->connection = new PDO($this->dbconfig['driver'] . "pgsql:host=" . $this->dbconfig['host']
                . ";dbname=" . $this->dbconfig['db'], $this->dbconfig['user'], $this->dbconfig['password']);
        }
        if ($this->connection) {
            $this->connection->query("use " . $this->dbconfig['db']);
            //print "connected";
        } else {
            //print "not connected";
        }
        //exit;
        $this->init_node_types();

        return $this;
    }


    function highlight_sql($query)
    {
        // Define CSS classes
        $keywordClass = 'sql-keyword';
        $stringClass = 'sql-string';
        $backtickClass = 'sql-backtick';
        $parenthesisClass = 'sql-parenthesis';
        $operatorClass = 'sql-operator';

        // Define SQL keywords
        $keywords = [
            'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'FROM', 'WHERE', 'AND', 'OR', 'NOT', 'NULL',
            'JOIN', 'LEFT', 'RIGHT', 'INNER', 'OUTER', 'ON', 'AS', 'IN', 'IS', 'BY', 'GROUP', 'ORDER',
            'HAVING', 'LIMIT', 'OFFSET', 'UNION', 'DISTINCT', 'COUNT', 'AVG', 'MIN', 'MAX', 'SUM'
        ];
        $keywordsPattern = implode('|', array_map('preg_quote', $keywords));

        // Tokenize and highlight the SQL query
        $pattern = "/('(?:''|[^'])*'|\"(?:\"\"|[^\"])*\"|`[^`]*`|\b($keywordsPattern)\b|[()=])/i";
        $highlightedQuery = preg_replace_callback($pattern, function ($matches) use ($keywordClass, $stringClass, $backtickClass, $parenthesisClass, $operatorClass) {
            if (isset($matches[2])) {
                return '<span class="' . $keywordClass . '">' . htmlspecialchars($matches[2]) . '</span>';
            } elseif (preg_match("/^'.*'$/s", $matches[0]) || preg_match('/^".*"$/s', $matches[0])) {
                return '<span class="' . $stringClass . '">' . htmlspecialchars($matches[0], ENT_QUOTES) . '</span>';
            } elseif (preg_match("/^`.*`$/", $matches[0])) {
                return '<span class="' . $backtickClass . '">' . htmlspecialchars($matches[0], ENT_QUOTES) . '</span>';
            } elseif ($matches[0] === '(' || $matches[0] === ')') {
                return '<span class="' . $parenthesisClass . '">' . htmlspecialchars($matches[0]) . '</span>';
            } elseif ($matches[0] === '=') {
                return '<span class="' . $operatorClass . '">' . htmlspecialchars($matches[0]) . '</span>';
            } else {
                return htmlspecialchars($matches[0]);
            }
        }, $query);
        $highlightedQuery .= "\n<style>
            .sql-keyword { color: #0b0bc7; font-weight: bold; }
            .sql-string { color: #2eb92e; font-weight: bold; padding: 2px;padding-left:5px; padding-right:5px;background-color: #e9e9e9 }
            .sql-backtick { color: brown; }
            .sql-parenthesis { color: #ff1bff; font-weight: bold;}
            .sql-operator { color: #c73232  ; }
            .sql-div {padding-left:50px; background-color: white;color:black;}
            </style>";
        return '<div class=sql-div><pre>' . $highlightedQuery . '</pre></div>';
    }
    /* debug a query by printing results as a table */
    function print_query_results($results)
    {
        if (!empty($results)) {
            echo $this->debug_style;
            echo '<table>';

            // Print headers
            echo '<tr>';
            $headers = array_keys($results[0]);
            foreach ($headers as $header) {
                echo '<th>' . htmlspecialchars($header) . '</th>';
            }
            echo '</tr>';

            // Print rows
            foreach ($results as $row) {
                echo '<tr>';
                $idx = 0;
                foreach ($headers as $key) {
                    $class = $key === 'id' ? 'id-cell' : '';
                    if ($idx == 0) {
                        $class = 'firstcol';
                        $idx++;
                    }
                    echo '<td class="' . $class . '">' . htmlspecialchars($row[$key]) . '</td>';
                }
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo 'No results found.';
        }
    }
    function display_table_details($tableName)
    {
        //print_r($this->config_object->getLegacyConfig());

        $output = "";
        try {
            $driver = $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);

            switch ($driver) {
                case 'mysql':
                    $fieldsQuery = "DESCRIBE $tableName";
                    $indexQuery = "SHOW INDEX FROM $tableName";
                    break;
                case 'pgsql':
                    $fieldsQuery = "
                            SELECT column_name AS field, data_type AS type, is_nullable AS nullable, column_default AS default_value
                            FROM information_schema.columns
                            WHERE table_name = '$tableName'";
                    $indexQuery = "
                            SELECT indexname AS index_name, indexdef AS index_definition
                            FROM pg_indexes
                            WHERE tablename = '$tableName'";
                    break;
                case 'sqlite':
                    $fieldsQuery = "PRAGMA table_info($tableName)";
                    $indexQuery = "PRAGMA index_list($tableName)";
                    break;
                default:
                    throw new Exception("Unsupported database driver: $driver");
            }

            $stmt = $this->connection->query($fieldsQuery);
            $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $this->connection->query($indexQuery);
            $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $output = $this->debug_style;

            $output .= "<h1 class='tabledetails'>$tableName Details</h1>";
            $output .= '<table>';
            $output .= '<tr>';
            foreach ($fields[0] as $field_name => $field_value) {
                $output .= "<th>$field_name</th>";
            }
            foreach ($fields as $field) {
                $output .=  '<tr>';
                foreach ($field as $key => $value) {
                    $output .=  '<td>' . htmlspecialchars($value) . '</td>';
                }
                $output .=  '</tr>';
            }
            $output .=  '</table>';

            // Display indexes
            $output .=  '<table>';
            foreach ($indexes[0] as $field_name => $field_value) {
                $output .=  "<th>$field_name</th>";
            }
            $output .=  '</tr>';
            foreach ($indexes as $index) {
                $output .=  '<tr>';
                if ($driver === 'sqlite') {
                    $output .=  '<td>' . htmlspecialchars($index['name']) . '</td>';
                    $output .=  '<td>' . htmlspecialchars($index['unique'] ? 'UNIQUE' : '') . '</td>';
                } else {
                    foreach ($index as $key => $value) {
                        $output .=  '<td>' . htmlspecialchars($value) . '</td>';
                    }
                }
                $output .=  '</tr>';
            }
            $output .=  '</table>';
        } catch (Exception $e) {
            $output .=  'Error: ' . $e->getMessage();
        }
        print $output;
    }

    public function init_node_types()
    {
        if ($this->connection) {
            $sql = "SELECT * FROM " . $this->osz_fields["tbl_node_types"];
            $stmt = $this->connection->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($results as $result) {
                //print ("<pre>");
                //print_r($result);
                $row = array('name' => $result['typeName']);
                foreach ($result as $key => $value) {
                    if (strstr($key, "fieldname") && $value) {
                        $row['fields'][str_replace("fieldname", "", $key)] = $value;
                    }
                }
                $node_types[$result['nodeTypeID']] = $row;
            }
            $this->node_types = $node_types;

            return $this->node_types;
        }
    }
    public function query_nodes($app)
    {
        $dbprefix = $app['dbprefix'] ?? "";
        $database = $app['database'] ?? "";
        if (isset($app['fields']) && $app['fields']) {
            $fields = $app['fields'];
        } else {
            $fields = ["id", "node_type", "status", "body", "slug", "parent", "title", "created", "updated"];
        }
        if (isset($app['slug-field']) && $app['slug-field']) {
            $slug_field = $app['slug-field'];
        } else {
            $slug_field = $this->osz_fields['node_slug'];
        }

        if (isset($app['table']) && $app['table']) {
            $table = $app['table'];
        } else {
            $table = $this->osz_fields["tbl_nodes"];
        }
        $content_group = $app['id'] ?? "";
        $limit = $app['limit'] ?? 15;
        $post_type = $app['post-type'] ?? [];
        $post_status = $app['post-status'] ?? [];
        if (!is_array($post_type))
            $post_type = $post_type;
        if (!is_array($post_status))
            $post_type = $post_status;

        $querystr = $app['path'] ?? "";

        $query = [
            "dbprefix" => $dbprefix,
            "table" => $table,
            "fields" => $fields,
            "where" => [
                ["field" => $this->osz_fields['node_group'], "value" => "$content_group"],
            ],
            "limit" => "$limit",
            "orderby" => "updated DESC"
        ];
        $where = [
            ["field" => $this->osz_fields['node_group'], "value" => "$content_group"],
        ];
        if ($app['where'] ?? false) {
            foreach ($app['where'] ?? [] as $clause) {
                $where[] = $clause;
            }
        } else {
            if ($querystr) {
                $where[] = ["type" => "AND", "field" => $slug_field, "value" => $querystr];
            }
        }
        if ($post_status) {
            $where[] = ["type" => "AND", "field" => "status", "value" => $post_status];
        }
        if ($post_type) {
            ["type" => "AND", "field" => "data_type", "value" => $post_type];
        }
        $query['where'] = $where;
        // if($post_type || ) {
        //     $query['where']=[
        //         ["type"=>"AND","field"=>"post_status","value"=>$post_status] ,
        //         ["type"=>"AND","field"=>"post_type","value"=>$post_type ] 
        //     ];
        //     // $query['where']=[["type"=>"AND","field"=>"post_type","value"=>["page",]] ];
        // }
        if ($database)
            $query['database'] = $database;
        // if($app['debug']??false) {
        //     $this->highlight_sql($query)
        // } else {

        // }
        $results = $this->get_data($query, $app['debug'] ?? false);
        //print_r($results);
        return $results;
    }
    public function get_node_types()
    {
        return $this->node_types;
    }
    public function get_data($queryparams, $show_query = false)
    {
        $config = $this->config_object->get('site');
        $limit = $config['limit'] ?? $this->config_object->get('defaults.limit')??20;

        $dbconfig = $this->dbconfig ?? false;
        //$database = $queryparams['database'] ?? $this->dbconfig['db'];
        if (!$this->dbconfig) {
            $this->connect();
        }

        $database = $queryparams['database'] ?? $this->dbconfig['db'];
        $table = $queryparams['table'] ?? 'nodes';
        $dbprefix = $queryparams['dbprefix'] ?? "";
        $field_array = $queryparams['fields'] ?? "*";
        $joins = $queryparams['joins'] ?? [];
        $where = $queryparams['where'] ?? [];
        $mastertable = $dbprefix . $table;
        $sort_type = $queryparams['orderby'] ?? "";
        if ($sort_type)
            $sort_type = "ORDER BY $sort_type";
        if ($database) {
            $mastertable = $database . ".$mastertable";
        }
        if (!is_array($field_array)) {
            $field_array = [$field_array];
        }
        $limit = $queryparams['limit'] ?? $limit ?? false;

        if (isset($queryparams['limit']) && ($queryparams['limit'] > 0)) {
            $limit = " LIMIT " . $queryparams['limit'] . " ";
        } else if (isset($this->config['limit']) && ($limit > 0)) {
            $limit = " LIMIT " . $limit . " ";
        } else {
            $limit = "";
        }

        $sql = "SELECT " . implode(",", $field_array) . "\n FROM $mastertable\n";
        if ($joins) {
            foreach ($joins as $join) {
                $FROM = $join['from'];
                $TO = $join['to'];
                $jointable = $dbprefix . $join['table'];
                if ($database) {
                    $jointable = "$database.$jointable";
                }
                $jointype = strtoupper($join['type'] ?? " ");
                if ($FROM && $TO && $jointable) {
                    $current =   " $jointype JOIN $jointable ON $FROM = $TO ";
                    $join_array[] = $current;
                }
            }
        }
        if ($join_array ?? []) {
            $sql .= implode("\n", $join_array);
        }

        if ($where) {
            $idx = 0;
            $where_array = [];
            $where_args = [];
            foreach ($where as $idx => $clause) {
                //print_r($details);print"\n";print_r($clause);exit;
                $field = $clause['field'] ?? "";
                $value = $clause['value'] ?? [];
                if (!is_array($value)) {
                    $value = [$value];
                }
                $idx2 = 0;
                $in_values = [];
                foreach ($value as $valuevar) {
                    $where_args[":param" . $idx .  "_$idx2"] = $valuevar;
                    $in_values[] = ":param" . $idx . "_$idx2";
                    $idx2 += 1;
                }
                $wheretype = $clause['type'] ?? "OR";
                if ($idx > 0) {
                    $where_array[] = " $wheretype $field IN (" . implode(", ", $in_values) . ")\n";
                    //$where_args [":$field"] = $value;
                } else {
                    $where_array[] = "\n $field  IN (" . implode(", ", $in_values) . ")\n";
                    //$where_args [":$field"] = $value;
                }
            }
        }
        if ($where_array ?? []) {
            $query_where = " " . implode("\n ", $where_array) . " ";
            $sql .= " WHERE " . $query_where;
        } else {
            $query_where = "";
        }
        $sql .= "\n $sort_type\n $limit";
        if (!$query_where) {
            try {
                $stmt = $this->connection->prepare($sql);
                $stmt->execute();
                $retval = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (exception $e) {
                $retval = false;
            }
        } else {
            try {
                $stmt = $this->connection->prepare($sql, array(PDO::FETCH_ASSOC, PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                $stmt->execute($where_args);
                $retval = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (exception $e) {
                $retval = false;
            }
        }
        //print_r($queryparams);

        if ($show_query) {
            print $this->highlight_sql($sql);
            // print "$query_where";
            //print_r($where_args);
            //print $sql;
            print "<div style='background-color:#fed;padding:5px;color:#900'><b>Error on query: </b>";
            print "<pre>";
            $stmt->debugDumpParams();
            print "</pre>";
            print "</div>";
            //exit;
            //print_r($query_where);exit;

        }
        return $retval;
    }

    public function query($sql, $filters = false)
    {
        return $this->fetch_query($sql, $filters);
    }
    public function fetch_query($sql, $filters = false)
    {
        //print($sql);exit;

        $dbname = $this->dbconfig['db'] ?? "";
        // if($dbname)
        // {
        //     print $dbname;exit;
        //     $this->connection->query("use " . $dbname);
        // }

        if ($this->connection) {
            if (!$filters) {
                //print $sql;exit;
                try {
                    $this->stmt = $this->connection->prepare($sql);
                    $this->stmt->execute();
                    return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (exception $e) {
                    print "<div style='background-color:#fed;padding:5px;color:#900'><b>Error on query: </b>";
                    print  'Error: ' . $e->getMessage();
                    print "</div>";

                    return false;
                }
            } else {
                $this->stmt = $this->connection->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                return $this->stmt->execute($filters);
            }
            return $this;
        } else {
            return false;
        }
    }
    public function fetchAll()
    {
        $stmt = $this->stmt->fetchAll() ?? false;
        return $stmt;
    }
    // function fetch_all($query)
    // {
    //    $resultArray="";
    //    $result=$this->DB_Query($query);
    //    while(($resultArray[] = @mysql_fetch_assoc($result)) || array_pop($resultArray));
    //    return $resultArray;
    //  }        
    function beginTrans()
    {
        return $this->query("BEGIN TRANSACTION;");
    }
    function commit()
    {
        return $this->query("COMMIT;");
    }
    function convertDate($theDate, $format)
    {
        return date($format, strtotime($theDate));
    }
    function insert_assoc($table, $array)
    {
        if (!$this->connection ?? false) {
            return false;
        } else {
            try {
                //$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);            
                $columns = implode(", ", array_keys($data));
                $values = ":" . implode(", :", array_keys($data));
                $sql = "INSERT INTO $tableName ($columns) VALUES ($values)";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);
            } catch (PDOException $e) {
                // Handle any database errors here
                return false;
                //die('Database error: ' . $e->getMessage());
            }
        }
    }
}
