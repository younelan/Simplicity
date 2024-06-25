<?php

require 'TaskManager.php';

$dsn = 'mysql:host=localhost;dbname=cto';
$username = 'cto';
$password = '';

$taskManager = new TaskManager($dsn, $username, $password);

$response = 'error';

if (isset($_GET['id'])) {
    $taskId = $_GET['id'];
    try {
        $taskManager->cancelTask($taskId);
        $response = 'success';
    } catch (Exception $e) {
        $response = $e->getMessage();
    }
}

echo $response;
?>

