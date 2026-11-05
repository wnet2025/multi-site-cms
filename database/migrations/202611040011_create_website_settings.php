<?php
declare(strict_types=1);
return ['name'=>'202611040011_create_website_settings','version'=>202611040011,'checksum'=>'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb','up'=>static function(PDO $pdo): void {$pdo->exec("CREATE TABLE website_settings (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,website_id BIGINT UNSIGNED NOT NULL,setting_key VARCHAR(100) NOT NULL,setting_value JSON NOT NULL,created_at DATETIME(6) NOT NULL,updated_at DATETIME(6) NOT NULL,PRIMARY KEY(id),UNIQUE KEY uq_settings_key(website_id,setting_key),CONSTRAINT fk_settings_website FOREIGN KEY(website_id) REFERENCES websites(id) ON UPDATE CASCADE ON DELETE RESTRICT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");}];

