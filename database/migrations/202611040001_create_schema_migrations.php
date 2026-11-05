<?php
declare(strict_types=1);
return ['name'=>'202611040001_create_schema_migrations','version'=>202611040001,'checksum'=>'1111111111111111111111111111111111111111111111111111111111111111','up'=>static function(PDO $pdo): void {$pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,migration_name VARCHAR(255) NOT NULL,version BIGINT UNSIGNED NOT NULL,checksum CHAR(64) NOT NULL,executed_at DATETIME(6) NOT NULL,PRIMARY KEY(id),UNIQUE KEY uq_migration_name(migration_name),UNIQUE KEY uq_migration_version(version)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");}];

