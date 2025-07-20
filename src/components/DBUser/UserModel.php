<?php 
namespace Opensitez\Simplicity\Components;
use Opensitez\Simplicity\MSG;

class UserModel extends \Opensitez\Simplicity\DBLayer {
    private $default_user_table = 'users';
    function get_user($app,$auth_user) {
        $dbprefix = $app['auth']['dbprefix']??"";
        $database = $app['auth']['database']??"";
        // print "<div>Getting user</div>";
        // print_r($auth_user);
        // exit;
        // print_r($app);exit;
        // $myquery="SELECT n.nid,n.title,n.type, b.body_value, a. FROM $database.$dbprefixnode n 
        // left join $database.$dbprefixfield_data_body b on n.nid = b.entity_id
        // left join $database.$dbprefixurl_alias a on a.source=concat('node/',node.nid)";
        //print "<div> ---    User {$auth_user['password']}</div>" . print_r($auth_user);
        $password=$auth_user['password']??"";
        $username=$auth_user['username']??"";
        $user_table=$app['auth']['table']??$this->default_user_table;
        $table=$app['auth']['table']??['users'];
        $user_field=$app['auth']['user-field']??'username';
        $password_field=$app['auth']['password-field']??'password';

        // print "<div>Checking user: $username</div>";
        // print "<div>Using table: $user_table</div>";
        // print "<div>Using user field: $user_field</div>";
        // print_r($password_field);
        // print "<div>Using password field: $password_field</div>";
        // print "<div>Using database: $database</div>";
        // print "<div>Using dbprefix: $dbprefix</div>";
        // print_r($app);
        // print_r($user_field);
        // print_r($user);
        $query = [
            "dbprefix" => $dbprefix,
            "table" => "$user_table u",
            "fields" => ["u.*"],
            "where" => [
                ["field"=>"$user_field","value"=>"$username"],
                ["type"=>"AND","field"=>"$password_field","value"=>"$password"],
            ],
        ];

        // if(!$username||!$password) {
        //     return false;
        // }
        if($database)  {
            $query['database']=$database;
        }
        //print_r($query);
        $results=$this->get_data($query,false);
        //print_r($results);
        //exit;
        return $results[0]??false;
    }
}

