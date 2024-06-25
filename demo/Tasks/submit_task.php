<?php

require 'TaskManager.php';

$dsn = 'mysql:host=localhost;dbname=cto';
$username = 'cto';
$password = '';

$taskManager = new TaskManager($dsn, $username, $password);

$response = [
    'success' => false,
    'message' => 'Failed to add task'
];

if (isset($_POST['description'])) {
    $description = $_POST['description'];
    $scheduledAt = !empty($_POST['scheduled_at']) ? date('Y-m-d H:i:s', strtotime($_POST['scheduled_at'])) : null;
    $data = ['description' => $description];
    try {
        $taskManager->addTask($data, $scheduledAt);
        $response['success'] = true;
        $response['message'] = 'Task added successfully';
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>

