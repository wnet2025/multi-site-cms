<?php
declare(strict_types=1);
namespace MultiSiteCms\Exceptions;
final class DomainNotFoundException extends HttpException { public function __construct() { parent::__construct('Domain not found.', 404); } }

