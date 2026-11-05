<?php
declare(strict_types=1);
namespace MultiSiteCms\Repositories;
interface DomainRepositoryInterface { public function findByNormalizedDomain(string $domain): ?array; }

