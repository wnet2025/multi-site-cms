<?php
declare(strict_types=1);
namespace MultiSiteCms\Services;
use MultiSiteCms\Exceptions\HttpException;
final class CsrfService
{
    public function token(): string { if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); } return $_SESSION['_csrf'] ??= bin2hex(random_bytes(32)); }
    public function validate(?string $token): void { if (!is_string($token) || !hash_equals($this->token(), $token)) { throw new HttpException('Invalid CSRF token.', 419); } }
}

