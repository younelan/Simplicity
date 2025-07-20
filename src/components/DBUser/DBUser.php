<?php

namespace Opensitez\Simplicity\Components;
use Opensitez\Simplicity\MSG;
require_once(__DIR__ . '/UserModel.php');

class DBUser extends \Opensitez\Simplicity\Component
{
    public $name = "DBUser";
    public $description = "Database User management";
    protected $user_field = 'username';
    protected $password_field = 'password';
    protected $default_user_table = 'users';
    protected $user = null;

    function on_event($event)
    {
        switch ($event["type"]) {
            case MSG::onComponentLoad:
                $this->framework->register_type("userprovider", "table");
                break;
        }
        return parent::on_event($event);
    }
    public function checkCredentials($username, $password)
    {
        $user_model = new UserModel($this->config_object);

        $user_model->set_framework($this->framework);
        $app = $this->config_object->get('site.current-route', []);
        $auth_user = [
            'username' => $username,
            'password' => $password
        ];
        // print "<div>Checking user: $username</div>";
        // print_r($auth_user);exit;
        $this->user = $user_model->get_user($app, $auth_user);
        if ($this->user) {
            // print "<div>Found user: " . print_r($this->user, true) . "</div>";
            return $this->user;
        } else {
            // print "<div>User not found or invalid credentials.</div>";
            return false;
        }
    }
    public function getUser() {
        return $this->user ?? null;
    }
}
