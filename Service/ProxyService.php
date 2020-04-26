<?php

namespace Pretorien\RequestBundle\Service;

use Pretorien\RequestBundle\Model\Proxy;
use Pretorien\RequestBundle\Entity\ProxyManager;

class ProxyService
{
    private $_proxyPool;
    private $_proxyManager;

    const PROXY_POOL_LENGTH = 10;

    public function __construct(ProxyManager $proxyManager)
    {
        $this->_proxyManager = $proxyManager;
        $this->_proxyPool = $this->generateProxyPool();
        return $this;
    }

    public function generateProxyPool($size = self::PROXY_POOL_LENGTH): ?array
    {
        $pool = [];
        $proxies = $this->_proxyManager->getRepository()->findBy(
            array('enable' => true),
            array('failure' => 'ASC', 'lastLatency' => 'ASC')
        );

        if (count($proxies) > 0) {
            foreach ($proxies as $proxy) {
                if (count($pool) >= $size) {
                    break;
                }
                $pool[] = $proxy;
            }
        }

        return $pool;
    }

    public function addFail($proxy)
    {
        // $this->_logger->info("ProxyService: add fail to $proxy");
        // $entityProxy = $this->_em->getRepository(EntityProxy::class)->findOneBy(
        //     array('host' => $proxy->getHost())
        // );
        // $entityProxy->setFailure($entityProxy->getFailure()+1);
        // $entityProxy->setLastFailure(new \Datetime());
        // if ($entityProxy->getFailure() > 5) {
        //     $entityProxy->setEnable(false);
        // }
        // $this->_em->persist($entityProxy);
        // $this->_em->flush();
    }

    public function hasValidProxyPool(): bool
    {
        return count($this->_proxyPool) > 0;
    }

    public function getRandomProxy(): ?Proxy
    {
        if (count($this->_proxyPool) == 0) {
            throw new \Exception("Proxy pool is empty", 1);
        }

        $proxy = current($this->_proxyPool);
        if (next($this->_proxyPool) == false) {
            reset($this->_proxyPool);
        }

        // $proxy = $this->proxyPool[mt_rand(0, count($this->proxyPool) - 1)];
        return $proxy;
    }
}
