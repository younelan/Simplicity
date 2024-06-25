<?php

class TaskManager {
    private $pdo;
    private $dsn;
    private $username;
    private $password;
    private $connected;

    public function __construct($dsn, $username, $password) {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->connect();
        $this->cronScheduler = new CronScheduler();
    }

    private function connect() {
        try {
            $this->pdo = new PDO($this->dsn, $this->username, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connected = true; // Set connected flag
        } catch (PDOException $e) {
            fwrite(STDERR, "Error connecting to database: " . $e->getMessage() . "\n");
            // Log error and continue without rethrowing exception
        }
    }

    private function ensureConnection() {
        try {
            $this->pdo->query('SELECT 1');
        } catch (PDOException $e) {
            fwrite(STDERR, "Reconnecting to database...\n");
            $this->connect();
        }
    }

    public function addTask($data, $scheduledAt = null) {
        try {
            $this->ensureConnection();
            $this->pdo->beginTransaction();

            if ($scheduledAt === null) {
                $scheduledAt = date('Y-m-d H:i:s');
            }

            $stmt = $this->pdo->prepare("INSERT INTO tasks (data, scheduled_at, status) VALUES (:data, :scheduled_at, 'pending')");
            $stmt->execute([
                'data' => json_encode($data),
                'scheduled_at' => $scheduledAt
            ]);

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            fwrite(STDERR, "Error adding task: " . $e->getMessage() . "\n");
        }
    }
    public function addRepeatingTask($data, $cronExpression) {
        try {
            $this->ensureConnection();
            $this->pdo->beginTransaction();

            $nextExecution = $this->cronScheduler->getNextCronTime($cronExpression);

            $stmt = $this->pdo->prepare("INSERT INTO repeating_tasks (data, cron_expression, next_execution) VALUES (:data, :cron_expression, :next_execution)");
            $stmt->execute([
                'data' => json_encode($data),
                'cron_expression' => $cronExpression,
                'next_execution' => $nextExecution
            ]);

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            fwrite(STDERR, "Error adding repeating task: " . $e->getMessage() . "\n");
        }
    }  
    public function cancelTask($taskId) {
        try {
            $this->ensureConnection();
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("UPDATE tasks SET status = 'cancelled' WHERE id = :id AND status = 'pending'");
            $stmt->execute(['id' => $taskId]);

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            fwrite(STDERR, "Error canceling task: " . $e->getMessage() . "\n");
        }
    }

    public function oldPollDatabase() {
        $this->ensureConnection(); // Ensure database connection

        try {
            $this->pdo->beginTransaction();

            // Select tasks that are pending and not timed out
            $stmt = $this->pdo->prepare("SELECT * FROM tasks WHERE status = 'pending' AND (scheduled_at IS NULL OR scheduled_at <= NOW()) LIMIT 1 FOR UPDATE");
            $stmt->execute();
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($task) {
                // Check if task has been running for more than 1 hour (3600 seconds)
                $startTime = strtotime($task['created_at']);
                $currentTime = time();
                $elapsedTime = $currentTime - $startTime;
                
                if ($elapsedTime > 3600) { // Mark as timeout if elapsed time exceeds 1 hour
                    $this->markTaskTimeout($task['id']);
                } else {
                    $this->markTaskActive($task['id']);
                }
                $this->pdo->commit();
            } else {
                $this->pdo->rollBack();
            }
            return $task;
        } catch (PDOException $e) {
            fwrite(STDERR, "Error polling database: " . $e->getMessage() . "\n");
            // Log error and continue without rethrowing exception
            $this->connected = false; // Reset connected flag (optional)
            return null; // Return null or handle gracefully based on your daemon logic
        }
    }
    public function pollDatabase() {
        try {
            $this->ensureConnection();
            $this->pdo->beginTransaction();

            // Poll for one-time tasks
            $stmt = $this->pdo->query("SELECT * FROM tasks WHERE status = 'pending' AND (scheduled_at IS NULL OR scheduled_at <= NOW()) LIMIT 1 FOR UPDATE");
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($task) {
                $this->markTaskActive($task['id']);
                $this->pdo->commit();
                return $task;
            }

            // Poll for repeating tasks
            $stmt = $this->pdo->query("SELECT * FROM repeating_tasks WHERE status = 'active' AND next_execution <= NOW() LIMIT 1 FOR UPDATE");
            $repeatingTask = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($repeatingTask) {
                $this->pdo->commit();
                // Convert the repeating task into a one-time task for execution
                $this->addTask(json_decode($repeatingTask['data'], true));
                $this->updateRepeatingTask($repeatingTask);
                return $repeatingTask;
            }

            $this->pdo->rollBack();
            return null;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            fwrite(STDERR, "Error polling database: " . $e->getMessage() . "\n");
        }
    }

    private function updateRepeatingTask($repeatingTask) {
        try {
            $this->ensureConnection();
            $this->pdo->beginTransaction();

            $nextExecution = $this->cronScheduler->getNextCronTime($repeatingTask['cron_expression']);

            $stmt = $this->pdo->prepare("UPDATE repeating_tasks SET last_execution = NOW(), next_execution = :next_execution WHERE id = :id");
            $stmt->execute([
                'next_execution' => $nextExecution,
                'id' => $repeatingTask['id']
            ]);

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            fwrite(STDERR, "Error updating repeating task: " . $e->getMessage() . "\n");
        }
    }

    public function markTaskActive($taskId) {
        try {
            $this->ensureConnection();
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("UPDATE tasks SET status = 'active' WHERE id = :id");
            $stmt->execute(['id' => $taskId]);

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            fwrite(STDERR, "Error marking task as active: " . $e->getMessage() . "\n");
        }
    }

    private function markTaskTimeout($taskId) {
        $this->ensureConnection(); // Ensure database connection

        try {
            $stmt = $this->pdo->prepare("UPDATE tasks SET status = 'timeout', finished_at = NOW() WHERE id = :id");
            $stmt->execute(['id' => $taskId]);
        } catch (PDOException $e) {
            fwrite(STDERR, "Error marking task as timeout: " . $e->getMessage() . "\n");
            // Log error and continue without rethrowing exception
            $this->connected = false; // Reset connected flag (optional)
        }
    }

    public function markTaskCompleted($taskId) {
        try {
            $this->ensureConnection();
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("UPDATE tasks SET status = 'completed', finished_at = NOW() WHERE id = :id");
            $stmt->execute(['id' => $taskId]);

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            fwrite(STDERR, "Error marking task as completed: " . $e->getMessage() . "\n");
        }
    }
    public function startRepeatingTask($taskId) {
        try {
            $this->ensureConnection();
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("UPDATE repeating_tasks SET status = 'active' WHERE id = :id");
            $stmt->execute(['id' => $taskId]);

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            fwrite(STDERR, "Error starting repeating task: " . $e->getMessage() . "\n");
        }
    }
    public function stopRepeatingTask($taskId) {
        try {
            $this->ensureConnection();
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("UPDATE repeating_tasks SET status = 'inactive' WHERE id = :id");
            $stmt->execute(['id' => $taskId]);

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            fwrite(STDERR, "Error stopping repeating task: " . $e->getMessage() . "\n");
        }
    }
    public function markTimeoutTasks() {
        try {
            $this->ensureConnection();
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("UPDATE tasks SET status = 'timeout', finished_at = NOW() WHERE status = 'active' AND TIMESTAMPDIFF(HOUR, scheduled_at, NOW()) > 1");
            $stmt->execute();

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            fwrite(STDERR, "Error marking timeout tasks: " . $e->getMessage() . "\n");
        }
    }
}

?>
