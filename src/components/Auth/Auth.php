<?php
namespace Opensitez\Simplicity\Components;
use Opensitez\Simplicity\MSG;

class Auth extends \Opensitez\Simplicity\Component
{
	private $auth_manager = null;
	private $auth = null;
    function on_event($event)
    {
		//print "hello from Auth component<br/>\n";
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
	function requireLogin()
	{
		if (!$this->auth_manager) {
			$this->onInit();
		}
		$this->auth_manager->requireLogin();
	}
	public function onInit()
	{
		//print "Initializing Auth component<br/>\n";
		//print_r($this->framework);

		$this->auth = $this->config_object->get('site.auth') ?? [];


		$this->auth_manager = new \Opensitez\Simplicity\SimpleAuth($this->config_object);
		$this->auth_manager->set_framework($this->framework);

		$login_path = $this->config_object->get('site.auth.login_template') ?? "login/login.html";
		//file_get_contents(__DIR__ . "/templates/$login_template",)
		$login_template = $this->load_template($login_path, $paths = [__DIR__ . "/templates/"]);
		$this->auth_manager->set_template($login_template);
		$this->auth_manager->initAuth();

	}
    public function onAuth($event)
    {
		$this->onInit();
		$this->auth = $this->config_object->get('site.current-route.auth') ?? [];

		//print "<strong>Debug:</strong> Processing auth for domain: " . $this->config_object->get('site.host', '') . "<br/>\n";exit;
		if($this->auth) {
			$this->requireLogin();
			
		}
        //session_start();
        //$this->initAuth();
        //$this->debug("<strong>Debug:</strong> Processing auth for domain: $this->domain<br/>");
        // if(true)
        // {
        //     $user = $this->requireLogin();
        //     if ($user) {
        //         $this->debug("<strong>Debug:</strong> User authenticated: " . print_r($user, true) . "<br/>");
        //         return $user;
        //     } else {
        //         return false;
        //         $this->debug("<strong>Debug:</strong> Authentication failed for domain: $this->domain<br/>");
        //     }
        // } else {
        //     print "<strong>Debug:</strong> No auth route matched for domain: $domain<br/>";
        // }
    }
}

