<?php
namespace Opensitez\Simplicity;
// Simple list of user auth, default auth for SimpleAuth
class SimpleUser extends Base {
    private $password_file = "adminprefs.php";
    private $users = [];
    private $password_field = 'password';
    private $user_field = 'user';

    public function generatePasswordFile()
    {
        $htpasswd = "<?php \n\n";
        foreach ($this->getUsers() as $key => $value) {
            $htpasswd .= "  \$pref_users['$key']=array(";
            foreach ($value as $key2 => $value2) {
                $htpasswd .= " '$key2' => '$value2' ,";
            }
            $htpasswd = substr($htpasswd, 0, (strlen($htpasswd) - 1));
            $htpasswd .= ");\n";
        }
        $htpasswd .= "\n\n?>";
        return $htpasswd;
    }

    public function writePasswordFile()
    {
        $htpasswd = $this->generatePasswordFile();
        $fh = fopen($this->password_file, 'w') or die('Check Permissions');
        fwrite($fh, $htpasswd);
        fclose($fh);
    }
    public function __construct($config_object = null) {
        parent::__construct($config_object);
        $users = $this->config_object->get('site.current-route.auth.users', $this->config_object->get('site.auth.users'));
        if (!$users) {
            $users = [];
        }
        $format = $this->config_object->get('site.current-route.auth.format', $this->config_object->get('site.auth.format', 'plaintext'));
        if($format === 'plaintext') {
            $users = array_map(function($user) {
                $user[$this->password_field] = md5($user[$this->password_field]);
                return $user;
            }, $users);
        }

        // print_r($users);exit;
        $this->setUsers($users);
    }
    public function setUsers($users) {
        $this->users = $users;
    }
    public function getUser($username) {
        return $this->users[$username] ?? null;
    }
    public function checkPassword($user, $password) {
        if (isset($this->users[$user]) && password_verify($password, $this->users[$user][$this->password_field])) {
            return true;
        }
        return false;
    }
    public function isLoggedIn($session) {
        return isset($session[$this->user_field]) && isset($session['password']) &&
            isset($this->users[$session[$this->user_field]]) && $session['password'] === $this->users[$session[$this->user_field]][$this->password_field];
    }
    public function setPassword($user, $new_password) {
        if (isset($this->users[$user])) {
            $this->users[$user][$this->password_field] = password_hash($new_password, PASSWORD_DEFAULT);
            return true;
        }
        return false;
    }
    public function getUsers() {
        return $this->users;
    }
}
