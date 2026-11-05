<?php

declare(strict_types=1);

namespace MultiSiteCms\Exceptions;

use RuntimeException;

class HttpException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 500)
    {
        parent::__construct($message);
    }
}

