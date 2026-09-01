<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            transaction_id VARCHAR(50) NOT NULL,
            product VARCHAR(100) NOT NULL,
            product_name VARCHAR(100) NOT NULL,
            reference VARCHAR(50) NOT NULL,
            amount VARCHAR(20) NOT NULL,
            details VARCHAR(400) NOT NULL,
            status VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};