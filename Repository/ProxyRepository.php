<?php

namespace WTeam\RequestBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use WTeam\RequestBundle\Entity\Proxy;

/**
 * @method Proxy|null find($id, $lockMode = null, $lockVersion = null)
 * @method Proxy|null findOneBy(array $criteria, array $orderBy = null)
 * @method Proxy[]    findAll()
 * @method Proxy[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProxyRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, Proxy::class);
    }

    public function findTooManyFailure($maxFailureNumber)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.failure > :maxFailureNumber')
            ->setParameter('maxFailureNumber', $maxFailureNumber);

        return $qb->getQuery()->getResult();
    }

    public function deleteTooManyFailure($maxFailureNumber)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->delete(Proxy::class, 'p');
        $qb->where('p.failure > :maxFailureNumber')
            ->setParameter('maxFailureNumber', $maxFailureNumber);

        return $qb->getQuery()->getResult();
    }
}
