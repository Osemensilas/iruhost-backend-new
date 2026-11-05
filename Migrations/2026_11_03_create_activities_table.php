<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_activities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            domain VARCHAR(100) NOT NULL,
            hosting VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            ssl_secure VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};