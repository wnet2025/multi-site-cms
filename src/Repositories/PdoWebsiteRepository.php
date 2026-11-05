<?php
declare(strict_types=1);
namespace MultiSiteCms\Repositories;
use PDO;
final class PdoWebsiteRepository implements WebsiteRepositoryInterface
{
    public function __construct(private readonly PDO $pdo) {}
    public function findEligibleById(int $id): ?array
    {
        $query = $this->pdo->prepare("SELECT id,name,status,default_locale,text_direction,timezone,template_id,template_version FROM websites WHERE id = :id AND deleted_at IS NULL AND status = 'published' LIMIT 1");
        $query->execute(['id' => $id]);
        $row = $query->fetch();
        return $row === false ? null : $row;
    }
}

