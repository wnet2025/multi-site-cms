<?php
declare(strict_types=1);
namespace MultiSiteCms\Services;
use MultiSiteCms\Exceptions\AuthenticationException;
use MultiSiteCms\Support\Configuration;
final class AdminAuthenticationService
{
    public function __construct(private readonly Configuration $configuration) {}
    public function configureSession(): void { ini_set('session.cookie_httponly', '1'); ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? '1' : '0'); ini_set('session.cookie_samesite', 'Lax'); ini_set('session.gc_maxlifetime', (string) $this->configuration->sessionLifetime); }
    public function login(string $username, string $password): void { $this->configureSession(); if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); } if (!hash_equals($this->configuration->adminUsername, $username) || !password_verify($password, $this->configuration->adminPasswordHash)) { throw new AuthenticationException(); } session_regenerate_id(true); $_SESSION['admin_authenticated'] = true; $_SESSION['authenticated_at'] = time(); }
    public function logout(): void { if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); } $_SESSION = []; if (ini_get('session.use_cookies')) { $params = session_get_cookie_params(); setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']); } session_destroy(); }
    public function isAuthenticated(): bool { $this->configureSession(); if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); } return ($_SESSION['admin_authenticated'] ?? false) === true; }
}

