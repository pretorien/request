<?php

namespace WTeam\RequestBundle\Service;

use WTeam\RequestBundle\Entity\Proxy as EntityProxy;
use WTeam\RequestBundle\Http\Proxy;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ProxyService
{
    private $em;
    private $proxyPool;
    private $logger;

    const PROXY_POOL_LENGTH = 10;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $em)
    {
        $this->logger = $logger;
        $this->em = $em;
        $this->proxyPool = $this->generateProxyPool();
        return $this;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function generateProxyPool($size = self::PROXY_POOL_LENGTH): ?array
    {
        $proxyPool = [];
        $proxyList = $this->em->getRepository(EntityProxy::class)->findBy(
            array('enable' => true),
            array('failure' => 'ASC', 'lastLatency' => 'ASC')
        );

        if (count($proxyList) > 0) {
            foreach ($proxyList as $proxy) {
                if (count($proxyPool) >= $size) {
                    break;
                }
                $proxyPool[] = Proxy::toPrototype($proxy);
            }
        }

        return $proxyPool;
    }

    public function hasValidProxyPool()
    {
        return count($this->proxyPool) > 0;
    }

    public function getRandomProxy()
    {
        $proxy = current($this->proxyPool);
        if(next($this->proxyPool) == false){
            reset($this->proxyPool);
        }
        // $proxy = $this->proxyPool[mt_rand(0, count($this->proxyPool) - 1)];
        $this->logger->info("ProxyService::getRandomProxy $proxy");
        return $proxy;
    }
}
