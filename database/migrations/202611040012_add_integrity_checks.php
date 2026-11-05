<?php
declare(strict_types=1);
return ['name'=>'202611040012_add_integrity_checks','version'=>202611040012,'checksum'=>'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc','up'=>static function(PDO $pdo): void {$pdo->exec("ALTER TABLE domains ADD CONSTRAINT chk_domains_domain_not_empty CHECK (CHAR_LENGTH(domain) > 0)");$pdo->exec("ALTER TABLE pages ADD CONSTRAINT chk_pages_slug_not_empty CHECK (CHAR_LENGTH(slug) > 0)");}];

