<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            product_id VARCHAR(36) NOT NULL,
            product VARCHAR(255) NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            billing VARCHAR(50) NOT NULL,
            domain VARCHAR(50) NOT NULL,
            url VARCHAR(255) NOT NULL,
            text VARCHAR(20) NOT NULL,
            expiry_date VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};