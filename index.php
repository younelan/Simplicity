<?php
	/* basically include and require login before any password protected page */
	require_once(__DIR__ . "/AuthInclude.php");
	require_once(__DIR__ . "/SimpleTemplate.php");

	$auth->require_login();

	//the script will require a login before continuing
	$content = "<h1 class=trailer>Member Zone</h1>";
	$content .= "<p>Hello ".$_SESSION['user_data']['first']."</p>";
	$content .= "This page uses the SimpleTemplate class to render the content of session after login.<p> This is a password protected page. It Won't display until you password protect";
	$content .= "<pre>";
	$content .= print_r($_SESSION,true) . "</pre><a class='logoff' href=?action=logoff>Click Here to logoff</a>";
	$template = new SimpleTemplate($config['login_template']??__DIR__ . '/templates/login.html');
	$template->setVars(['content'=>$content]);
	print $template->render();
