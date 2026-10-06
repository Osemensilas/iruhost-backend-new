<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS support_chats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_id VARCHAR(36) NOT NULL,
            sender_id VARCHAR(36) NOT NULL,
            sender VARCHAR(100) NOT NULL,
            reciever_id VARCHAR(255) NOT NULL,
            message VARCHAR(300) NOT NULL,
            status VARCHAR(50) NOT NULL,
            image VARCHAR(255) NOT NULL,
            avatar VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};