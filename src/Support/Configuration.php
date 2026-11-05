<?php

declare(strict_types=1);

namespace MultiSiteCms\Support;

use RuntimeException;

final class Configuration
{
    public function __construct(public readonly string $adminUsername, public readonly string $adminPasswordHash, public readonly int $sessionLifetime)
    {
    }

    public static function fromEnvironment(): self
    {
        $username = self::required('ADMIN_USERNAME');
        $hash = self::required('ADMIN_PASSWORD_HASH');
        if (password_get_info($hash)['algo'] === 0) {
            throw new RuntimeException('ADMIN_PASSWORD_HASH is not a valid password hash.');
        }
        $lifetime = (int) (getenv('SESSION_LIFETIME') ?: 3600);
        if ($lifetime < 300) {
            throw new RuntimeException('SESSION_LIFETIME must be at least 300 seconds.');
        }
        return new self($username, $hash, $lifetime);
    }

    private static function required(string $name): string
    {
        $value = getenv($name);
        if ($value === false || $value === '') {
            throw new RuntimeException("Missing environment variable: {$name}");
        }
        return $value;
    }
}

