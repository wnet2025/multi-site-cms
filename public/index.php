<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

use MultiSiteCms\Controllers\AuthController;
use MultiSiteCms\Controllers\PublicResolutionController;
use MultiSiteCms\Http\JsonResponse;
use MultiSiteCms\Http\Request;
use MultiSiteCms\Routing\Router;

$request = Request::fromGlobals();
$router = new Router();
$authController = new AuthController($auth, $csrf);
$publicController = new PublicResolutionController($resolver);
$router->add('GET', '/', [$publicController, 'resolve']);
$router->add('POST', '/admin/api/login', [$authController, 'login']);
$router->add('POST', '/admin/api/logout', [$authController, 'logout']);
$router->add('GET', '/admin/api/auth/status', [$authController, 'status']);

try {
    $response = $router->dispatch($request);
    if (!$response instanceof JsonResponse) { $response = new JsonResponse(200, ['ok' => true]); }
} catch (Throwable $exception) {
    error_log($exception->__toString());
    $status = $exception instanceof \MultiSiteCms\Exceptions\HttpException ? $exception->status : 500;
    $response = new JsonResponse($status, ['error' => ['message' => $status >= 500 ? 'Internal server error.' : $exception->getMessage()]]);
}
$response->send();

