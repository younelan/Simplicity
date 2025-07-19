<?php
namespace Opensitez\Simplicity;
// Simple list of user auth
class SimpleUser extends Base {
    private $password_file = "adminprefs.php";
    private $users = [];
    private $password_field = 'password';
    private $user_field = 'user';

    public function generate_password_file()
    {
        $htpasswd = "<?php \n\n";
        foreach ($this->get_users() as $key => $value) {
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

    public function write_password_file()
    {
        $htpasswd = $this->generate_password_file();
        $fh = fopen($this->password_file, 'w') or die('Check Permissions');
        fwrite($fh, $htpasswd);
        fclose($fh);
    }
    public function __construct($config_object = null) {
        parent::__construct($config_object);
        $users = $this->config_object ? $this->config_object->get('users') : [];
        $this->set_users($users);
    }
    public function set_users($users) {
        $this->users = $users;
    }
    public function get_user($username) {
        return $this->users[$username] ?? null;
    }
    public function check_password($user, $password) {
        if (isset($this->users[$user]) && password_verify($password, $this->users[$user][$this->password_field])) {
            return true;
        }
        return false;
    }
    public function is_logged_in($session) {
        return isset($session[$this->user_field]) && isset($session['password']) &&
            isset($this->users[$session[$this->user_field]]) && $session['password'] === $this->users[$session[$this->user_field]][$this->password_field];
    }
    public function set_password($user, $new_password) {
        if (isset($this->users[$user])) {
            $this->users[$user][$this->password_field] = password_hash($new_password, PASSWORD_DEFAULT);
            return true;
        }
        return false;
    }
    public function get_users() {
        return $this->users;
    }
}
