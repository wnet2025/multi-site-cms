<?php

declare(strict_types=1);

namespace MultiSiteCms\Database;

use PDO;
use RuntimeException;

final class Connection
{
    public static function fromEnvironment(): PDO
    {
        $required = static function (string $name): string {
            $value = getenv($name);
            if ($value === false || $value === '') {
                throw new RuntimeException("Missing environment variable: {$name}");
            }
            return $value;
        };
        return new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $required('DB_HOST'), $required('DB_PORT'), $required('DB_DATABASE')),
            $required('DB_USERNAME'),
            $required('DB_PASSWORD'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
}

