<?php

declare(strict_types=1);

namespace MultiSiteCms\Http;

class Response
{
    public function __construct(public readonly int $status, public readonly string $body, public readonly array $headers = ['Content-Type' => 'text/plain; charset=utf-8'])
    {
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) { header("{$name}: {$value}"); }
        echo $this->body;
    }
}

