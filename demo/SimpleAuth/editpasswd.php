<?php
require_once("passwd.php");
require_once("backend/defaults.php");
require_once("adminprefs.php");

// $auth->require_login();	
// print_r($_POST);
// print_r($_SESSION);
//exit;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$success = $auth->edit_password();
	if (!$success) {
		$auth->show_edit_password_form();
		exit;
	} else {
		$vars['contentafter'] = nl2br(htmlentities($auth->generate_password_file()));
		$auth->set_vars($vars);
	}
}
$success = $auth->show_edit_password_form();
	// }
	// print "success";
/*

	$lang = $config['lang']??'en';
	$translations = $config['translations'][$lang]??[];

	if (isset($_POST['login']) AND isset($_POST['pass']) AND isset($_POST['pass']) ) {
		$login = $_POST['login'];
		$pass_crypte = crypt($_POST['pass'],$login); // On crypte le mot de passe

		if($_POST['pass']==$_POST['pass2']) {
			$pref_users[$login]["Password"]=$pass_crypte;

			$zzz= "\$pref_password='$pass_crypte';\n \n \$pref_login = '$login'; \n ?>";
			//write includes htpass
	//			$htpasswd="<? \n\n \$login = '$login'; \n \$pref_password= '$pass_crypte' \n";



			echo get_translation("Password Changed");
		} else {
			echo get_translation("Passwords Don't Match");
		}
	} else { //unfilled form

	}
*/