<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            reciever_id VARCHAR(255) NOT NULL,
            message VARCHAR(300) NOT NULL,
            status VARCHAR(20) NOT NULL,
            image VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};