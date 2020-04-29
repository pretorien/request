<?php

namespace Pretorien\RequestBundle\Fetcher;

use Pretorien\RequestBundle\Entity\ProxyManager;
use Pretorien\RequestBundle\Service\RequestService;

abstract class AbstractFetcher
{
    protected $_requestService;
    protected $_proxyManager;
    protected $_configuration;
    protected $_options;

    /**
     * AbstractFetcher constructor
     *
     * @param RequestService $requestService
     * @param ProxyManager   $proxyManager
     * @param array          $configuration
     * @param array          $options
     */
    public function __construct(
        RequestService $requestService,
        ProxyManager $proxyManager,
        array $configuration,
        array $options = []
    ) {
        $this->_requestService = $requestService;
        $this->_proxyManager = $proxyManager;
        $this->_configuration = $configuration;
        $this->_options = $options;
    }
}
