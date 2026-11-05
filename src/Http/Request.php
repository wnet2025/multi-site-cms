<?php

declare(strict_types=1);

namespace MultiSiteCms\Http;

final class Request
{
    public function __construct(private readonly string $method, private readonly string $path, private readonly string $host, private readonly array $body, private readonly array $headers)
    {
    }

    public static function fromGlobals(): self
    {
        $body = json_decode((string) file_get_contents('php://input'), true);
        return new self(strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'), parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', $_SERVER['HTTP_HOST'] ?? '', is_array($body) ? $body : $_POST, getallheaders() ?: []);
    }

    public function method(): string { return $this->method; }
    public function path(): string { return $this->path; }
    public function host(): string { return $this->host; }
    public function body(): array { return $this->body; }
    public function header(string $name): ?string
    {
        foreach ($this->headers as $key => $value) { if (strcasecmp($key, $name) === 0) { return is_string($value) ? $value : null; } }
        return null;
    }
}

