<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id int AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(100),
            role VARCHAR(50),
            permission VARCHAR(50),
            firstname VARCHAR(100),
            lastname VARCHAR(100),
            email VARCHAR(100) UNIQUE,
            password VARCHAR(100),
            referred_by VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};