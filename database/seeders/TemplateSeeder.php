<?php

declare(strict_types=1);


final class TemplateSeeder
{
    public function __construct(private readonly PDO $pdo, private readonly string $path)
    {
    }

    public function run(): int
    {
        if (!is_dir($this->path)) {
            return 0;
        }
        $count = 0;
        foreach (glob($this->path . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'template.json') ?: [] as $file) {
            $data = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
            foreach (['template_id', 'name', 'version', 'directory'] as $key) {
                if (!isset($data[$key]) || !is_string($data[$key]) || $data[$key] === '') {
                    throw new RuntimeException("Invalid template definition: {$file}");
                }
            }
            $statement = $this->pdo->prepare("INSERT INTO templates (template_id,name,version,directory,description,status,created_at,updated_at) VALUES (?,?,?,?,?,'active',UTC_TIMESTAMP(6),UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE name=VALUES(name),version=VALUES(version),directory=VALUES(directory),description=VALUES(description),status='active',updated_at=UTC_TIMESTAMP(6)");
            $statement->execute([$data['template_id'], $data['name'], $data['version'], $data['directory'], $data['description'] ?? null]);
            $count += $statement->rowCount() > 0 ? 1 : 0;
        }
        return $count;
    }
}

