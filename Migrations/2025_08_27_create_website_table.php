<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS websites (
            id int AUTO_INCREMENT PRIMARY KEY,
            web_id VARCHAR(100),
            category VARCHAR(255),
            sub_category VARCHAR(255),
            web_name VARCHAR(255),
            image VARCHAR(255),
            image2 VARCHAR(255),
            image3 VARCHAR(255),
            image4 VARCHAR(255),
            description VARCHAR(800),
            features VARCHAR(800),
            stack VARCHAR(200),
            price VARCHAR(20),
            old_price VARCHAR(20),
            url VARCHAR(100),
            delivery_type VARCHAR(100),
            licence_type VARCHAR(100),
            version VARCHAR(100),
            database_type VARCHAR(50),
            auth VARCHAR(50),
            file_path TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};