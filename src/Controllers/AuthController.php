<?php
declare(strict_types=1);
namespace MultiSiteCms\Controllers;
use MultiSiteCms\Http\JsonResponse;
use MultiSiteCms\Http\Request;
use MultiSiteCms\Services\AdminAuthenticationService;
use MultiSiteCms\Services\CsrfService;
final class AuthController
{
    public function __construct(private readonly AdminAuthenticationService $auth, private readonly CsrfService $csrf) {}
    public function login(Request $request): JsonResponse { $body = $request->body(); $this->auth->login((string) ($body['username'] ?? ''), (string) ($body['password'] ?? '')); return new JsonResponse(200, ['authenticated' => true, 'csrf_token' => $this->csrf->token()]); }
    public function logout(Request $request): JsonResponse { $this->csrf->validate($request->header('X-CSRF-Token') ?? ($request->body()['_csrf'] ?? null)); $this->auth->logout(); return new JsonResponse(200, ['authenticated' => false]); }
    public function status(Request $request): JsonResponse { return new JsonResponse(200, ['authenticated' => $this->auth->isAuthenticated(), 'csrf_token' => $this->auth->isAuthenticated() ? $this->csrf->token() : null]); }
}

