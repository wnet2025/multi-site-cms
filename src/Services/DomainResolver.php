<?php
declare(strict_types=1);
namespace MultiSiteCms\Services;
use MultiSiteCms\Exceptions\DomainNotFoundException;
use MultiSiteCms\Exceptions\WebsiteUnavailableException;
use MultiSiteCms\Repositories\DomainRepositoryInterface;
use MultiSiteCms\Repositories\WebsiteRepositoryInterface;
final class DomainResolver
{
    public function __construct(private readonly DomainRepositoryInterface $domains, private readonly WebsiteRepositoryInterface $websites, private readonly DomainNormalizer $normalizer) {}
    public function resolve(string $host): array
    {
        $domain = $this->domains->findByNormalizedDomain($this->normalizer->normalize($host));
        if ($domain === null) { throw new DomainNotFoundException(); }
        $website = $this->websites->findEligibleById((int) $domain['website_id']);
        if ($website === null) { throw new WebsiteUnavailableException(); }
        return ['domain' => $domain, 'website' => $website];
    }
}

