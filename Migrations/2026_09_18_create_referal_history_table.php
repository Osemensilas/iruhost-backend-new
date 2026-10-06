<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS referal_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            referer_id VARCHAR(36) NOT NULL,
            refered_id VARCHAR(36) NOT NULL,
            product VARCHAR(255) NOT NULL,
            commission DECIMAL(10,2) NOT NULL,
            transaction VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};