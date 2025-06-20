<?php
namespace Opensitez\Simplicity;
class SimpleAuth
{
    private $config = [];
    private $users;
    private $translations;
    private $template;
    private $vars = [];
    private $lang = 'en';
    private $form_template;
    private $edit_password_template;
    private $user_field = 'user';
    private $password_field = 'password';
    private $password_file = "adminprefs.php";
    private $errors = [];
    public function set_users($users)
    {
        $this->users = $users;
    }
    public function __construct($config)
    {

        if(!$config) {
            $config = new \Opensitez\Simplicity\Config();
        } elseif (is_array($config)) {
            $config = new \Opensitez\Simplicity\Config($config);
        } 

        $this->config = $config;
        $this->users = $config->get('users') ?? [];

        $this->vars = $config->get('vars') ?? [];
        $this->lang = $config->get('lang') ?? 'en';

        $this->translations = $config->get('translations') ?? [];

        $this->template = $config->get('template') ?? '{{content}}';  // Default page template
        $this->form_template = '
            <h1 class="widgetheader">{{Please Login}}</h1>
			<table class="loginform">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="{{csrf_token}}" />
                <tr><td><label>{{Login}}</label></td><td><input type="text" name="login" /></td></tr>
                <tr><td><label>{{Password}}</label></td><td><input type="password" name="password" /></td></tr>
                <tr class=trailer><td colspan=2>{{trailer}}</td></tr>
                <tr><td colspan=2 align=right><button type="submit">{{Connect}}</button></td></tr>
            </form>
			</table>
        ';
        $this->edit_password_template = '
            <h1 class="widgetheader">{{Update Password}}</h1>
            <form method="POST" action="?action=change_password">
                <input type="hidden" name="csrf_token" value="{{csrf_token}}" />
                <table class="loginform">
                <tr><td><label>{{Current Password}}</label></td><td><input type="password" name="current_password" /></td></tr>
                <tr><td><label>{{New Password}}</label></td><td><input type="password" name="new_password" /></td></tr>
                <tr><td><label>{{Confirm Password}}</label></td><td><input type="password" name="confirm_password" /></td></tr>
                <tr><td colspan=2 align=right><button type="submit">{{Update Password}}</button></td></tr>
                <tr class=trailer><td colspan=2>{{trailer}}</td></tr>
                </table>
            </form>
        ';

        session_start();
        $this->generate_csrf_token();
    }

    // Set the template string
    public function set_template($template_str)
    {
        $this->template = $template_str;
    }

    // Set the form template string
    public function set_form_template($template_str)
    {
        $this->form_template = $template_str;
    }

    // Set the edit password form template string
    public function set_edit_password_template($template_str)
    {
        $this->edit_password_template = $template_str;
    }

    // Set the variables for substitution
    public function set_vars($vars)
    {
        $this->vars = $vars;
    }

    // Generate a CSRF token and store it in the session
    private function generate_csrf_token()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    // Validate the CSRF token
    private function validate_csrf_token($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    // Substitute variables in the template
    private function substitute_vars($template, $vars = false)
    {
        //print_r($vars) ;exit;
        if (!$vars) {
            $vars = $this->vars ?? [];
        }
        foreach ($vars ?? [] as $key => $value) {
            if (!is_array($value)) {
                $template = str_replace('{{' . $key . "}}", $value, $template);
            }
        }
        $template = str_replace("{{nocache}}", "", $template);
        $template = str_replace("{{/nocache}}", " ", $template);
        return $template;
    }

    // Display the login form
    public function show_login_form()
    {
        //print_r($this->translations);
        $users = $this->users;
        //print_r($users);
        //print "<hr/>";
        //print_r($this->config->get('users'));exit;

        exit;
        $this->vars['csrf_token'] = $_SESSION['csrf_token'];
        $this->vars['content'] = $this->substitute_vars($this->form_template);
        //print($this->vars['content']);exit;
        
        $this->vars['content'] = $this->substitute_vars($this->vars['content'], $this->translations[$this->lang ?? "en"]);
        $this->vars['trailer'] = $this->translations[$this->lang]['Login Required'] ?? 'Login Required';
        if ($this->errors) {
            foreach ($this->errors as $idx => $error) {
                $this->errors[$idx] = $this->translations[$this->lang][$error] ?? $error;
            }
            $this->vars['trailer'] .= "<br/>" . implode("<br/>", $this->errors);
        }
        $template = $this->substitute_vars($this->template, $this->translations);
        $template = $this->substitute_vars($template, $this->vars);
        echo $template;
        exit;
    }

    // Display the edit password form
    public function show_edit_password_form()
    {
        $this->vars['csrf_token'] = $_SESSION['csrf_token'];
        $this->vars['trailer'] = "";
        if ($this->errors) {
            $this->vars['trailer'] = implode("<br/>", $this->errors);
        }
        $this->vars['csrf_token'] = $_SESSION['csrf_token'];
        $this->vars['content'] = $this->substitute_vars($this->edit_password_template);
        $this->vars['content'] = $this->substitute_vars($this->vars['content'], $this->translations);
        $template = $this->substitute_vars($this->template, $this->translations);
        $template = $this->substitute_vars($template, $this->vars);
        echo $template;
    }

    // Try to login
    public function login($redirect_url = null)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && $this->validate_csrf_token($_POST['csrf_token'])) {
            //print "<div> this is a post, checking";
            if (isset($_POST['login']) && isset($_POST['password'])) {
                //print "<div>Both login and password provided to login</div>";
                $login = $_POST['login'];
                $password = $_POST['password'];
                $valid_auth = $this->check_password($login, $password);
                //print_r($this->users);
                //print "$login $password $valid_auth";exit;
                //print "<div>pass $password $crypted_password</div>";
                if (isset($this->users[$login]) && password_verify($password, $this->users[$login][$this->password_field])) {
                    //print "<div>Login success</div>";
                    $_SESSION[$this->user_field] = $login;
                    $_SESSION['password'] = $this->users[$login][$this->password_field];
                    $_SESSION['login_time'] = time();
                    $_SESSION['user_data'] = $this->users[$login];
                    session_regenerate_id(true); // Regenerate session ID
                    if ($redirect_url) {
                        header("Location: $redirect_url");
                        exit;
                    }
                    return true;
                } else {
                    $this->errors[] = "Failed Login";
                    return false;
                    // Log the failed login attempt
                    //error_log("Failed login attempt for user: $login");
                }
            }
        } else {
            $this->errors[] = "Invalid CSRF token";
        }
        return false;
    }

    // Require login or die
    public function require_login($redirect_url = null)
    {
        $action = $_GET['action'] ?? "";
        if ($action == "logoff") {
            $this->logoff();
            die();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $status = $this->login($redirect_url);
            // if($status) {
            //     print "<div>Woohoo, we're in</div>";
            // } else {
            //     print "<div>not in, will need requiring in</div>";
            // }
        }
        if (!$this->is_logged_in()) {
            // print "<div>yeah 
            // requiring in</div>";
            $this->show_login_form();
            die();
        } else {
            // print "<div>is logged in</div>";
        }
    }

    // Check if user is logged in
    public function is_logged_in()
    {
        return isset($_SESSION[$this->user_field]) && isset($_SESSION['password']) &&
            isset($this->users[$_SESSION[$this->user_field]]) && $_SESSION['password'] === $this->users[$_SESSION[$this->user_field]][$this->password_field];
    }

    // Log off
    public function logoff($redirect_url = null)
    {
        session_destroy();
        $redirect_url = $this->vars['logoff_url'] ?? '?';
        if ($redirect_url) {
            header("Location: $redirect_url");
            exit;
        }
    }
    function check_password($user, $password)
    {
        $crypt_pass = crypt($user, $password);
        //print_r($this->users[$user]);
        //print "<div>Received $password, crypted: $crypt_pass= " . $this->users[$user][$this->password_field] . "</div>";
        if (isset($this->users[$user]) && password_verify($password, $this->users[$user][$this->password_field])) {
            return true;
        } else {
            return false;
        }
    }

    // Edit password
    public function generate_password_file()
    {
        $htpasswd = "<?php \n\n";
        foreach ($this->users as $key => $value) {
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
        $passwords = $this->generate_password_file();
        $fh = fopen($this->password_file, 'w') or die($this->get_translations('Check Permissions'));
        fwrite($fh, $htpasswd);
        fclose($fh);
    }
    public function edit_password()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && $this->validate_csrf_token($_POST['csrf_token'])) {
            if (isset($_POST['current_password']) && isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];

                if ($new_password !== $confirm_password) {
                    $this->errors[] = $this->get_translation("New password and confirm password do not match");
                    return false;
                }

                if (!$this->validate_password($new_password)) {

                    return false;
                }

                $user = $_SESSION[$this->user_field];
                if (password_verify($current_password, $this->users[$user][$this->password_field])) {
                    //if ($this->check_password($current_password, $this->users[$user][$this->password_field])) {
                    $this->users[$user][$this->password_field] = password_hash($new_password, PASSWORD_DEFAULT);
                    $_SESSION['password'] = $this->users[$user]; // Update session password
                    $this->message = $this->get_translation("Password changed successfully");
                    return true;
                } else {
                    $this->this[] = $this->get_translation("Current password is incorrect");
                    return false;
                }
            }
        } else {
            $this->errors[] = $this->get_translation("Invalid CSRF token");
        }
        return false;
    }
    private function get_translation($untranslated)
    {
        return $this->translations[$untranslated] ?? $untranslated;
    }
    // Validate password complexity
    private function validate_password($password)
    {
        if (strlen($password) < 8) {
            $this->errors[] = "Password must be at least 8 characters long.";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $this->errors[] = "Password must contain at least one uppercase letter.";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $this->errors[] = "Password must contain at least one lowercase letter.";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $this->errors[] = "Password must contain at least one digit.";
        }
        if (!preg_match('/[\W_]/', $password)) {
            $this->errors[] = "Password must contain at least one special character.";
        }

        if ($this->errors) {
            return false;
        } else {
            return true;
        }
    }
}
