<?php
declare(strict_types=1);
namespace MultiSiteCms\Repositories;
interface WebsiteRepositoryInterface { public function findEligibleById(int $id): ?array; }

