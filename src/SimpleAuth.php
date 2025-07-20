<?php
namespace Opensitez\Simplicity;

class SimpleAuth extends \Opensitez\Simplicity\Component
{
    private $user_manager;
    private $translations;
    private $template;
    private $vars = [];
    private $lang = 'en';
    private $form_template;
    private $edit_password_template;
    private $user_field = 'user';
    private $password_field = 'password';
    private $password_file = "adminprefs.php";
    private $currentRoute = [];
    private $domain = '';
    private $defaults = [];
    private $login_template = '';
    private $authType = 'simple';
    private $errors = [];
    public function setUsers($users)
    {
        $this->user_manager->setUsers($users);
    }
    public function __construct($config_object = null)
    {
        parent::__construct($config_object);
    }
    public function initAuth() {
        session_start();
        $this->generate_csrf_token();
        $this->currentRoute = $this->config_object->get('site.current-route', []);
        //print "<strong>Debug:</strong> Initializing SimpleAuth component.<br/>\n";
        $this->login_template = $this->load_template("login/login_template.tpl");
        $this->domain = $this->config_object->get('site.host', '');
        $this->defaults = $this->config_object->get('auth.defaults', []);
        $auth = $this->config_object->get('site.current-route.auth', $this->config_object->get('site.auth'));
        $this->vars = $auth['vars'] ??[];

        $auth_type = $auth['type'] ?? 'simple';

       
        if ($auth) {
            $this->authType = $auth['type'] ?? $defaults['type'] ?? 'simple';
            $this->user_manager = $this->framework->get_registered_type('userprovider', strtolower($this->authType));

            if ($this->user_manager) {
                $this->debug("<strong>Debug:</strong> Found auth component: $this->authType<br/>");
                $this->user_manager->on_event(['type' => MSG::onAuth, 'domain' => $this->domain]);
            } else {
                $this->debug("<strong>Debug:</strong> No auth component found for type: $this->authType, using default<br/>");
                //$this->user_manager = $this->framework->get_registered_type("userprovider", "simple");
                 $this->user_manager = new SimpleUser($this->config_object);
            }
        }	else {
            $this->debug("<strong>Debug:</strong> No auth configuration found, using default simple user manager.<br/>");
        }

        $this->lang = $this->config_object ? $this->config_object->get('lang') ?? 'en' : 'en';
        $this->translations = $this->config_object ? $this->config_object->get('translations') ?? [] : [];
        $this->template = $this->config_object ? $this->config_object->get('template') ?? '{{content}}' : '{{content}}';

        $this->edit_password_template = $this->load_template('login/edit_password.tpl');
        $this->form_template = $this->load_template('login/login_template.tpl');

    }
    public function set_template($template_str)
    {
        $this->template = $template_str;
    }
    public function set_form_template($template_str)
    {
        $this->form_template = $template_str;
    }
    public function set_edit_password_template($template_str)
    {
        $this->edit_password_template = $template_str;
    }
    public function set_vars($vars)
    {
        $this->vars = $vars;
    }
    private function generate_csrf_token()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
    private function validate_csrf_token($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    public function showLoginForm()
    {
        //print "<div>Showing login form</div>";
        $vars = $this->vars;
        $vars['csrf_token'] = $_SESSION['csrf_token'];
        $vars['content'] = $this->substitute_vars($this->form_template, $vars);
        $vars['content'] = $this->substitute_vars($vars['content'], $this->translations[$this->lang ?? "en"]);
        $vars['trailer'] = $this->translations[$this->lang]['Login Required'] ?? 'Login Required';
        $vars['content'] = $this->substitute_vars($vars['content'], $vars);
        if ($this->errors) {
            foreach ($this->errors as $idx => $error) {
                $this->errors[$idx] = $this->translations[$this->lang][$error] ?? $error;
            }
            $vars['trailer'] .= "<br/>" . implode("<br/>", $this->errors);
        } else {
            $vars['trailer'] = "";
        }
        $template = $this->substitute_vars($this->template, $this->translations[$this->lang ?? "en"]);
        
        $template = $this->substitute_vars(   $template, $vars);
        echo $template;
        exit;
    }
    public function show_edit_password_form()
    {
        $this->vars['csrf_token'] = $_SESSION['csrf_token'];
        $this->vars['trailer'] = "";
        if ($this->errors) {
            $this->vars['trailer'] = implode("<br/>", $this->errors);
        }
        $this->vars['csrf_token'] = $_SESSION['csrf_token'];
        $this->vars['content'] = $this->substitute_vars($this->edit_password_template, $this->vars);
        $this->vars['content'] = $this->substitute_vars($this->vars['content'], $this->translations);
        $template = $this->substitute_vars($this->template, $this->translations);
        $template = $this->substitute_vars($template, $this->vars);
        echo $template;
    }
    public function login($redirect_url = null)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && $this->validate_csrf_token($_POST['csrf_token'])) {
            if (isset($_POST['login']) && isset($_POST['password'])) {
                $login = $_POST['login'];
                $password = $_POST['password'];
                $valid_auth = $this->user_manager->checkCredentials($login, $password);
                if ($valid_auth) {
                    //print "<div>Valid auth for user: $login</div>";
                    //$login = $valid_auth['username'] ?? $login; // Ensure we have the username
                    $_SESSION[$this->user_field] = $login;
                    $_SESSION['login'] = $login;
                    $_SESSION['password'] = $valid_auth[$this->password_field];
                    $_SESSION['login_time'] = time();
                    $_SESSION['user_data'] = $valid_auth;
                    session_regenerate_id(true); // Regenerate session ID
                    if ($redirect_url) {
                        header("Location: $redirect_url");
                        exit;
                    }
                    return true;
                } else {
                    $this->errors[] = "Failed Login";
                    return false;
                }
            }
        } else {
            $this->errors[] = "Invalid CSRF token";
        }
        return false;
    }

    // Require login or die
    public function requireLogin($redirect_url = null)
    {
        //print_r($_SESSION);
        $action = $_GET['action'] ?? "";
        if ($action == "logoff") {
            $this->logoff();
            die();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $status = $this->login($redirect_url);
        }
        if (!$this->isLoggedIn()) {
            //print "<div>yeah requiring in</div>";
            $this->showLoginForm();
            die();
        } else {
             //print "<div>is logged in</div>";
        }
    }
    public function isLoggedIn($session = false)
    {
        return $this->user_manager->isLoggedIn();
    }
    public function logoff($redirect_url = null)
    {
        session_destroy();
        $redirect_url = $this->vars['logoff_url'] ?? '?';
        if ($redirect_url) {
            header("Location: $redirect_url");
            exit;
        }
    }
    function checkCredentials($user, $password)
    {
        return $this->user_manager->checkCredentials($user, $password);
    }
    public function generatePasswordFile()
    {
        return $this->user_manager->generatePasswordFile();
    }
    public function writePasswordFile()
    {
        $this->user_manager->writePasswordFile();
    }
    public function editPassword()
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
                if ($this->user_manager->checkCredentials($user, $current_password)) {
                    $this->user_manager->setPassword($user, $new_password);
                    $_SESSION['password'] = $this->user_manager->getUser($user)[$this->password_field]; // Update session password
                    $this->message = $this->get_translation("Password changed successfully");
                    return true;
                } else {
                    $this->errors[] = $this->get_translation("Current password is incorrect");
                    return false;
                }
            }
        } else {
            $this->errors[] = $this->get_translation("Invalid CSRF token");
        }
        return false;
    }
    private function validatePassword($password)
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
