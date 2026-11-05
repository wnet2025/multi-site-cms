<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Support/Configuration.php';
require_once dirname(__DIR__) . '/src/Http/Request.php';
require_once dirname(__DIR__) . '/src/Http/Response.php';
require_once dirname(__DIR__) . '/src/Http/JsonResponse.php';
require_once dirname(__DIR__) . '/src/Exceptions/HttpException.php';
require_once dirname(__DIR__) . '/src/Exceptions/DomainNotFoundException.php';
require_once dirname(__DIR__) . '/src/Exceptions/WebsiteUnavailableException.php';
require_once dirname(__DIR__) . '/src/Exceptions/AuthenticationException.php';
require_once dirname(__DIR__) . '/src/Database/Connection.php';
require_once dirname(__DIR__) . '/src/Repositories/WebsiteRepositoryInterface.php';
require_once dirname(__DIR__) . '/src/Repositories/DomainRepositoryInterface.php';
require_once dirname(__DIR__) . '/src/Repositories/PdoWebsiteRepository.php';
require_once dirname(__DIR__) . '/src/Repositories/PdoDomainRepository.php';
require_once dirname(__DIR__) . '/src/Services/DomainNormalizer.php';
require_once dirname(__DIR__) . '/src/Services/DomainResolver.php';
require_once dirname(__DIR__) . '/src/Services/AdminAuthenticationService.php';
require_once dirname(__DIR__) . '/src/Services/CsrfService.php';
require_once dirname(__DIR__) . '/src/Routing/Router.php';
require_once dirname(__DIR__) . '/src/Controllers/AuthController.php';
require_once dirname(__DIR__) . '/src/Controllers/PublicResolutionController.php';

use MultiSiteCms\Database\Connection;
use MultiSiteCms\Repositories\PdoDomainRepository;
use MultiSiteCms\Repositories\PdoWebsiteRepository;
use MultiSiteCms\Services\AdminAuthenticationService;
use MultiSiteCms\Services\CsrfService;
use MultiSiteCms\Services\DomainNormalizer;
use MultiSiteCms\Services\DomainResolver;

$configuration = MultiSiteCms\Support\Configuration::fromEnvironment();
$pdo = Connection::fromEnvironment();
$normalizer = new DomainNormalizer();
$resolver = new DomainResolver(new PdoDomainRepository($pdo), new PdoWebsiteRepository($pdo), $normalizer);
$auth = new AdminAuthenticationService($configuration);
$csrf = new CsrfService();

