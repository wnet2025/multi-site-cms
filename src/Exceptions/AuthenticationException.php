<?php
declare(strict_types=1);
namespace MultiSiteCms\Exceptions;
final class AuthenticationException extends HttpException { public function __construct() { parent::__construct('Invalid credentials.', 401); } }

