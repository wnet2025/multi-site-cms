<?php

declare(strict_types=1);

namespace MultiSiteCms\Database;

use PDO;
use RuntimeException;
use Throwable;

final class MigrationRunner
{
    public function __construct(private readonly PDO $pdo, private readonly string $path)
    {
    }

    public function ensureTrackingTable(): void
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, migration_name VARCHAR(255) NOT NULL, version BIGINT UNSIGNED NOT NULL, checksum CHAR(64) NOT NULL, executed_at DATETIME(6) NOT NULL, PRIMARY KEY (id), UNIQUE KEY uq_migration_name (migration_name), UNIQUE KEY uq_migration_version (version)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
    }

    /** @return list<array{name:string,version:int,checksum:string,up:callable}> */
    public function discover(): array
    {
        $files = glob($this->path . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files, SORT_STRING);
        $result = [];
        $names = [];
        $versions = [];
        $previousVersion = null;
        foreach ($files as $file) {
            $baseName = basename($file, '.php');
            if (preg_match('/^(\d{12})_[a-z0-9_]+$/', $baseName, $matches) !== 1) {
                throw new RuntimeException("Invalid migration filename/order: {$baseName}.php");
            }
            $fileVersion = (int) $matches[1];
            $migration = require $file;
            if (!is_array($migration) || !isset($migration['name'], $migration['version'], $migration['checksum'], $migration['up']) || !is_callable($migration['up'])) {
                throw new RuntimeException("Invalid migration: {$file}");
            }
            $name = (string) $migration['name'];
            $version = (int) $migration['version'];
            if ($version !== $fileVersion || $name !== $baseName) {
                throw new RuntimeException("Migration filename mismatch: {$baseName}.php");
            }
            if (isset($names[$name])) {
                throw new RuntimeException("Duplicate migration name: {$name}");
            }
            if (isset($versions[$version])) {
                throw new RuntimeException("Duplicate migration version: {$version}");
            }
            if ($previousVersion !== null && $version <= $previousVersion) {
                throw new RuntimeException("Invalid migration ordering: {$name}");
            }
            $names[$name] = true;
            $versions[$version] = true;
            $previousVersion = $version;
            $result[] = $migration;
        }
        return $result;
    }

    /** @return array<string,array{version:int,checksum:string,executed_at:string}> */
    public function executed(): array
    {
        $this->ensureTrackingTable();
        $rows = $this->pdo->query('SELECT migration_name, version, checksum, executed_at FROM schema_migrations ORDER BY version')->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['migration_name']] = ['version' => (int) $row['version'], 'checksum' => (string) $row['checksum'], 'executed_at' => (string) $row['executed_at']];
        }
        return $result;
    }

    public function migrate(): int
    {
        $this->ensureTrackingTable();
        $executed = $this->executed();
        $applied = 0;
        foreach ($this->discover() as $migration) {
            $name = (string) $migration['name'];
            if (isset($executed[$name])) {
                if ($executed[$name]['checksum'] !== $migration['checksum']) {
                    throw new RuntimeException("Checksum mismatch: {$name}");
                }
                continue;
            }
            try {
                ($migration['up'])($this->pdo);
                $statement = $this->pdo->prepare('INSERT INTO schema_migrations (migration_name, version, checksum, executed_at) VALUES (?, ?, ?, UTC_TIMESTAMP(6))');
                $statement->execute([$name, $migration['version'], $migration['checksum']]);
                $applied++;
            } catch (Throwable $exception) {
                throw new RuntimeException("Migration failed: {$name}: {$exception->getMessage()}", 0, $exception);
            }
        }
        return $applied;
    }
}

