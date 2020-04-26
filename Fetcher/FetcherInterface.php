<?php

namespace Pretorien\RequestBundle\Fetcher;

use Pretorien\RequestBundle\Entity\ProxyManager;
use Pretorien\RequestBundle\Service\RequestService;

interface FetcherInterface
{
    public function __construct(
        RequestService $requestService,
        ProxyManager $proxyManager,
        array $configuration,
        array $options = []
    );
    public function fetch(): array;
}
