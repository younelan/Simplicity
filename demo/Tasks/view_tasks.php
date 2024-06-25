<?php

require 'TaskManager.php';

$dsn = 'mysql:host=localhost;dbname=cto';
$username = 'cto';
$password = '';

$taskManager = new TaskManager($dsn, $username, $password);
$tasks = $taskManager->getTasks();

$template = file_get_contents('task_form.html');
$tasksJson = json_encode(array_map(function($task) {
    $task['data'] = json_decode($task['data'], true);
    return $task;
}, $tasks));

$output = str_replace('{{tasks}}', $tasksJson, $template);
echo $output;

?>

