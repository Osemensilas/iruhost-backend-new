<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS referral_bonus (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            referral_id VARCHAR(36) NOT NULL,
            product VARCHAR(100) NOT NULL,
            package VARCHAR(100) NOT NULL,
            amount VARCHAR(20) NOT NULL,
            bonus VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};