<?php
declare(strict_types=1);
namespace MultiSiteCms\Repositories;
use PDO;
final class PdoDomainRepository implements DomainRepositoryInterface
{
    public function __construct(private readonly PDO $pdo) {}
    public function findByNormalizedDomain(string $domain): ?array
    {
        $query = $this->pdo->prepare('SELECT id,website_id,domain,is_primary FROM domains WHERE domain = :domain LIMIT 1');
        $query->execute(['domain' => $domain]);
        $row = $query->fetch();
        return $row === false ? null : $row;
    }
}

