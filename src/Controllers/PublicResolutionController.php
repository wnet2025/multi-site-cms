<?php

declare(strict_types=1);

namespace MultiSiteCms\Controllers;

use MultiSiteCms\Http\JsonResponse;
use MultiSiteCms\Http\Request;
use MultiSiteCms\Services\DomainResolver;

final class PublicResolutionController
{
    public function __construct(private readonly DomainResolver $resolver)
    {
    }

    public function resolve(Request $request): JsonResponse
    {
        $context = $this->resolver->resolve($request->host());
        return new JsonResponse(200, ['website' => $context['website'], 'domain' => $context['domain']['domain']]);
    }
}

