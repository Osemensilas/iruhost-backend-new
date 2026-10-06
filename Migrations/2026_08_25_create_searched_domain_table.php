<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS searched_domain (
            id INT AUTO_INCREMENT PRIMARY KEY,
            domain_id VARCHAR(36) NOT NULL,
            domain VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};