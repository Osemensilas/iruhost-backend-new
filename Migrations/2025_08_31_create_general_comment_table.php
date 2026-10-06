<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS general_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            comment_id VARCHAR(36) NOT NULL,
            comment VARCHAR(500) NOT NULL,
            comment_reply VARCHAR(2000) NOT NULL,
            reply_by VARCHAR(36) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};