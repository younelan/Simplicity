<?php

$translations = [
    'fr' => [
        'Password' => 'Mot de passe',
        'Login' => 'Identifiant',
        'Connect' => 'Connecter',
        'Current Password' => 'Mot de passe Actuel',
        'New Password' => 'New Password',
        'Confirm Password' => 'Confirmer Mot de Passe',
        'One Digit' => 'Password must contain at leat one Digit',
        'One Uppercase' => 'Password must contain at leat one Uppercase',
        'One Lowercase' => 'Password must contain at leat one Lowercase',
        'One Special' => 'Password must contain at leat one Special Character',
        'Min Length' => 'Password must be at least 8 characters long',
        'Edit Password' => 'Changer Mot de passe',
        'Change Password' => 'Changer Mot de Passe',
        'Password Changed' => 'Mot de passe Changé',
        'Password Change Failed' => 'Changement de Mot de passe échoué',
        'Current Password Incorrect' => 'Mot de passe Actuel Incorrect',
        'Please Login' => 'Veuillez Connecter',
        'Please Enter Password' => "Entrer Mot de Passe",
        "Update Password" => "Mettre à Jour Mot de passe",
        'Login Required' => "Mot de Passe Requis",
    ]
];

/* generate the english translation, basicaly all array_keys */
$keys = array_keys($translations['fr']);
$translations['en'] = array_combine($keys, $keys);

$config = [
    'translations' => $translations,
    'template' => 'templates/loginform.html'
];
