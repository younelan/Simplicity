<?php
namespace Opensitez\Simplicity\Components;
use Opensitez\Simplicity\MSG;

class Auth extends \Opensitez\Simplicity\Component
{
    public $name = "Auth";
    public $description = "Authentication component for user management";

    private $authComponent = null;
    private $users = [];
    private $user_field = 'username';
    private $password_field = 'password';
    private $authType = 'simple';
    private $currentRoute = [];
    private $domain = '';
    private $defaults = [];
    private $translations = [];
    private $lang = 'fr';
    private $login_template = '';
    private $template = "<div>{{content}}</div>";
    private $errors = [];
    private $user = null;
    private $vars = [];
    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::onComponentLoad:
                $this->framework->register_type('authprovider', 'auth');
                break;
            case MSG::onAuth:
                $this->debug("<strong>Debug:</strong> Auth event triggered.<br/>");
                $this->onAuth($event);
        }
        return parent::on_event($event);
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
    public function show_login_form()
    {
        $vars = $this->vars;
        $this->generate_csrf_token();
        $vars['csrf_token'] = $_SESSION['csrf_token']?? false;
        if(!$vars['csrf_token']) {
            $this->errors[] = "CSRF token generation failed.";
        }
        $vars['content'] = $this->substitute_vars($this->login_template, $vars);
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
    public function initAuth() {
        $this->currentRoute = $this->config_object->get('site.current-route', []);
        $this->login_template = $this->load_template("login/login_template.tpl");
        $this->domain = $this->config_object->get('site.host', '');
        $this->defaults = $this->config_object->get('auth.defaults', []);


        $this->translations = $this->config_object->get('translations', []);
  		if (!isset($this->translations['en'])) {
			$keys = array_keys($this->translations['fr']??[]);
			$this->translations['en'] = array_combine($keys, $keys);
            $this->config_object->set('translations', $this->translations);
		}
		if (!isset($this->translations[$this->config_object->get('lang')])) {
			$this->config_object->set('lang', $this->lang);
		} else {
            $this->lang = $this->config_object->get('lang');
        }

        if ($this->currentRoute['auth']?? null) {
            $this->authType = $this->currentRoute['auth']['type'] ?? $defaults['type'] ?? 'simple';
            $this->authComponent = $this->framework->get_registered_type('userprovider', strtolower($this->authType));

            if ($this->authComponent) {
                $this->debug("<strong>Debug:</strong> Found auth component: $this->authType<br/>");
                $this->authComponent->on_event(['type' => MSG::onAuth, 'domain' => $this->domain]);
            } else {
                $this->debug("<strong>Debug:</strong> No auth component found for type: $this->authType<br/>");
                $this->authComponent = $this->framework->get_registered_type("userprovider", "simple");
            }
        }

    }
    public function checkUser($username, $password)
    {
        if (!$this->authComponent) {
            $this->debug("<strong>Debug:</strong> No auth component initialized.<br/>");
            return false;
        }
        $user = $this->authComponent->checkUser($username, $password);
        if ($user) {
            $_SESSION[$this->user_field] = $user[$this->user_field];
            $_SESSION[$this->password_field] = $user[$this->password_field];
            return $user;
        } else {
            return false;
        }
        // else {
        //     header('WWW-Authenticate: Basic realm="Restricted Area"');
        //     header('HTTP/1.0 401 Unauthorized');
        //     return false;
        // }
    }
    public function onAuth($event)
    {
        session_start();
        $this->initAuth();
        $this->debug("<strong>Debug:</strong> Processing auth for domain: $this->domain<br/>");
        if(true)
        {
            $user = $this->requireLogin();
            if ($user) {
                $this->debug("<strong>Debug:</strong> User authenticated: " . print_r($user, true) . "<br/>");
                return $user;
            } else {
                return false;
                $this->debug("<strong>Debug:</strong> Authentication failed for domain: $this->domain<br/>");
            }
        } else {
            print "<strong>Debug:</strong> No auth route matched for domain: $domain<br/>";
        }
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
    public function isLoggedIn()
    {
        return isset($_SESSION[$this->user_field]) && isset($_SESSION['password']) &&
            isset($this->users[$_SESSION[$this->user_field]]) && $_SESSION['password'] === $this->users[$_SESSION[$this->user_field]][$this->password_field];
    }
    public function translateString($string)
    {
        if (isset($this->translations[$this->lang][$string])) {
            return $this->translations[$this->lang][$string];
        } else {
            return $string; // Return the original string if no translation is found
        }
    }
    public function requireLogin()
    {
        $vars = $this->config_object->get('site.vars', []);
        $vars['trailer'] = $this->translateString("Login Required");
        if (!$this->isLoggedIn()) {
            $this->show_login_form();
            // $this->debug("<strong>Debug:</strong> User not logged in, redirecting to login page.<br/>");
            // $login_str = $this->load_template("login/login_template.tpl");
            // $login_str = $this->substitute_vars($login_str,$this->translations[$this->lang]);
            // $login_str = $this->substitute_vars($login_str, $vars);
            // print "<div class='login-form'>$login_str</div>";
            // exit;
   
            //$this->logoff();
        } else {
            $this->debug("<strong>Debug:</strong> User is logged in.<br/>");
        }
    }
}
