<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cart (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            cart_id VARCHAR(36) NOT NULL,
            product VARCHAR(255) NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            renew DECIMAL(10,2) NOT NULL,
            billing VARCHAR(100) NOT NULL,
            domain VARCHAR(50) NOT NULL,
            currency VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};