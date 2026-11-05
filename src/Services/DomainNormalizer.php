<?php
declare(strict_types=1);
namespace MultiSiteCms\Services;
use MultiSiteCms\Exceptions\HttpException;
final class DomainNormalizer
{
    public function normalize(string $host): string
    {
        $host = trim($host);
        if ($host === '' || str_contains($host, '/') || str_contains($host, '?') || str_contains($host, '#') || str_contains($host, ':') || preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $host) === 1) { throw new HttpException('Invalid host.', 400); }
        $host = strtolower(rtrim($host, '.'));
        if (str_starts_with($host, 'www.')) { $host = substr($host, 4); }
        if (strlen($host) > 253 || preg_match('/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $host) !== 1) { throw new HttpException('Invalid host.', 400); }
        return $host;
    }
}

