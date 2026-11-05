<?php
declare(strict_types=1);
return ['name'=>'202611040002_create_templates','version'=>202611040002,'checksum'=>'2222222222222222222222222222222222222222222222222222222222222222','up'=>static function(PDO $pdo): void {$pdo->exec("CREATE TABLE templates (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,template_id VARCHAR(100) NOT NULL,name VARCHAR(160) NOT NULL,version VARCHAR(40) NOT NULL,directory VARCHAR(160) NOT NULL,description TEXT NULL,status ENUM('active','inactive') NOT NULL DEFAULT 'active',created_at DATETIME(6) NOT NULL,updated_at DATETIME(6) NOT NULL,PRIMARY KEY(id),UNIQUE KEY uq_templates_template_id(template_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");}];

