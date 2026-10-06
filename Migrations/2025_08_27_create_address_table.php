<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS address (
            id int AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(100),
            address1 VARCHAR(255),
            address2 VARCHAR(255),
            city VARCHAR(50),
            state VARCHAR(50),
            country VARCHAR(50),
            zip VARCHAR(20),
            cCode VARCHAR(20),
            phone VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};