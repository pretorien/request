<?php

namespace Pretorien\RequestBundle\Fetcher;

use Pretorien\RequestBundle\Entity\ProxyManager;
use Pretorien\RequestBundle\Service\RequestService;

class NordVpnFetcher extends AbstractFetcher implements FetcherInterface
{
    public const NAME = "nordvpn";
    public const DESCRIPTION = "";

    private const STATUS_ONLINE = "online";
    private const TECHNOLOGIE_PROXY = "HTTP Proxy";

    /**
     * NordVpnFetcher constructor
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
        if (!isset($configuration[self::NAME])) {
            throw new \Exception(
                "Merci de configurer les informations nécessaires à NordVpn (api, username et password)",
                1
            );
        }

        parent::__construct($requestService, $proxyManager, $configuration, $options);
    }

    /**
     * Fetch all NordVPN proxies
     *
     * @return array
     */
    public function fetch(): array
    {
        $proxies = $this->_requestService
                        ->publicRequest(
                            $this->_configuration[self::NAME]['api']
                        )
                        ->toArray();
                       
        return $this->transform($proxies);
    }

    /**
     * Transform API data to proxy model
     *
     * @param array $proxies
     * @return array
     */
    private function transform(array $proxies): array
    {
        $result = [];
        foreach ($proxies as $nordvpnProxy) {
            if ($nordvpnProxy['status'] == self::STATUS_ONLINE) {
                $proxy = $this->_proxyManager->createProxy();
                $proxy->setHost($nordvpnProxy['hostname']);
                $proxy->setPort(80);
                $proxy->setUsername($this->_configuration[self::NAME]['username']);
                $proxy->setPassword($this->_configuration[self::NAME]['password']);
                $proxy->setEnable(self::checkTechnologies($nordvpnProxy['technologies']));
                $result[] = $proxy;
            }
        }
        return $result;
    }

    /**
     * Check if proxy is indicated as online
     *
     * @param array $technologies
     * @return boolean
     */
    private static function checkTechnologies(array $technologies): bool
    {
        foreach ($technologies as $technologie) {
            if ($technologie['name'] == self::TECHNOLOGIE_PROXY) {
                return $technologie['pivot']['status'] == self::STATUS_ONLINE;
            }
        }
        return false;
    }
}
