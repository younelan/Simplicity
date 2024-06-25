DROP TABLE IF EXISTS tasks;

-- Create tasks table with updated schema
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data TEXT NOT NULL,
    scheduled_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    finished_at TIMESTAMP NULL DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending'
);

DROP TABLE IF EXISTS repeating_tasks;

CREATE TABLE repeating_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data JSON NOT NULL,
    cron_expression VARCHAR(255) NOT NULL,
    next_execution DATETIME NOT NULL,
    last_execution DATETIME NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active'
);