<?php
/* simple list of users to be used for login demo, you probably want a database instead */
$pref_users = [
  'admin' => ['first' => 'Simple Demo', 'last' => 'User', 'password' => 'admin', 'group' => 'demo-admin'],
  'user' => ['first' => 'Simple Demo', 'last' => 'User', 'password' => 'user', 'group' => 'demo-users'],
];

/* for demo purposes encrypt the passwords, you would not want to store them in plain text */
foreach ($pref_users as $user => $details) {
  $pref_users[$user]['password'] = password_hash($details['password'], PASSWORD_DEFAULT);
}
