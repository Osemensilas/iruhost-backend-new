<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS searched_domains (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            domain VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};