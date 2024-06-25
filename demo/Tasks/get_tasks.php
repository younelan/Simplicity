<?php

require 'TaskManager.php';

$dsn = 'mysql:host=localhost;dbname=cto';
$username = 'cto';
$password = '';

$taskManager = new TaskManager($dsn, $username, $password);
$tasks = $taskManager->getTasks();

foreach ($tasks as &$task) {
    $task['data'] = json_decode($task['data'], true);
}

header('Content-Type: application/json');
echo json_encode($tasks);
?>

