<?php
require 'TaskManager.php';

$dsn = 'mysql:host=localhost;dbname=cto';
$username = 'cto';
$password = '';

$taskManager = new TaskManager($dsn, $username, $password);

// Schedule a task with due_date omitted
$taskData = [
    'task_data' => [
        'title' => 'Example Task',
        'description' => 'This is an example task to be scheduled.'
    ]
];

if ($taskManager->scheduleTask($taskData)) {
    echo "Task scheduled successfully.\n";
} else {
    echo "Failed to schedule task.\n";
}

// Poll the database for pending tasks and process them
if ($taskManager->pollDatabase()) {
    echo "Tasks polled and processed successfully.\n";
} else {
    echo "Failed to poll tasks.\n";
}

// Retrieve tasks
$tasks = $taskManager->getTasks();
foreach ($tasks as $task) {
    echo "Task ID: {$task['id']}, Title: {$task['task_data']['title']}, Status: {$task['status']}\n";
}
