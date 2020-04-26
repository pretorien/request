<?php

namespace Pretorien\RequestBundle\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

/**
 * Default ORM ProxyManager.
 *
 */
class ProxyManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $class;

    /**
     * Constructor.
     *
     * @param EntityManager            $em
     * @param string                   $class
     */
    public function __construct(EntityManager $em, $class)
    {
        $this->em = $em;
        $this->repository = $em->getRepository($class);

        $metadata = $em->getClassMetadata($class);
        $this->class = $metadata->name;
    }

    /**
     *
     * @return void
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Delete all proxies
     *
     * @param integer|null $maxFailure
     * @return void
     */
    public function dropProxies(?int $maxFailure = null)
    {
        foreach ($this->repository->findAll() as $proxy) {
            if (
                is_null($maxFailure) ||
                $proxy->getFailure() >= $maxFailure
            ) {
                $this->em->remove($proxy);
            }
        }
        $this->em->flush();
    }

    /**
     * Return one proxy by id
     *
     * @param int $id
     * @return void
     */
    public function findProxyById(int $id)
    {
        return $this->repository->find($id);
    }

    /**
     * Return on proxy by host
     *
     * @param string $host
     * @return void
     */
    public function findProxyByHost(string $host)
    {
        return $this->repository->findOneBy(['host' => $host]);
    }

    /**
     * Create Proxy object
     *
     * @return void
     */
    public function createProxy()
    {
        $class = $this->getClass();
        return new $class();
    }

    /**
     * Save proxy
     *
     * @param Proxy $proxy
     * @return void
     */
    public function saveProxy(Proxy $proxy): bool
    {
        $this->doSaveProxy($proxy);
        return true;
    }

    /**
     * Persist and flush proxy
     *
     * @param Proxy $proxy
     * @return void
     */
    protected function doSaveProxy(Proxy $proxy)
    {
        $this->em->persist($proxy);
        $this->em->flush();
    }

    /**
     * Increment proxy failure
     *
     * @param Proxy $proxy
     * @return void
     */
    public function incFailure(Proxy $proxy)
    {
        $failure = $proxy->getFailure() + 1;
        $proxy->setFailure($failure);
        $proxy->setLastFailure(new \DateTime());
        return $this->saveProxy($proxy);
    }
}
