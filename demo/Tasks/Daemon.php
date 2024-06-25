<?php

require 'TaskManager.php';

class Daemon {
    private $taskManager;

    public function __construct($taskManager) {
        $this->taskManager = $taskManager;
    }

    public function run() {
        while (true) {
            try {
                $task = $this->taskManager->pollDatabase();
                if ($task) {
                    $pid = pcntl_fork();
                    if ($pid == -1) {
                        // Fork failed
                        error_log("Failed to fork process");
                    } elseif ($pid) {
                        // Parent process
                        pcntl_wait($status); // Protect against zombie children
                    } else {
                        // Child process
                        $this->handleTask(json_decode($task['data'], true));
                        $this->taskManager->markTaskCompleted($task['id']);
                        exit(0);
                    }
                }
            } catch (Exception $e) {
                // Log the error
                error_log($e->getMessage());
            }
            sleep(10); // Adjust the polling interval as needed
        }
    }

    private function handleTask($task) {
        // Simulate task handling
        sleep(5); // Replace with actual task logic
    }
}

$dsn = 'mysql:host=localhost;dbname=cto';
$username = 'cto';
$password = '';
$taskManager = new TaskManager($dsn, $username, $password);
$daemon = new Daemon($taskManager);
$daemon->run();

?>
