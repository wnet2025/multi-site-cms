<?php

declare(strict_types=1);

namespace MultiSiteCms\Http;

final class JsonResponse extends Response
{
    public function __construct(int $status, array $data)
    {
        parent::__construct($status, (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), ['Content-Type' => 'application/json; charset=utf-8']);
    }
}

