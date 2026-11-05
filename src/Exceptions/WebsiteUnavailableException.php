<?php
declare(strict_types=1);
namespace MultiSiteCms\Exceptions;
final class WebsiteUnavailableException extends HttpException { public function __construct() { parent::__construct('Website unavailable.', 404); } }

