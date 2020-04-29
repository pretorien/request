<?php

namespace Pretorien\RequestBundle\Entity;

use Pretorien\RequestBundle\Model\Proxy as ProxyModel;
use Doctrine\ORM\Mapping as ORM;

/**
 * Default ORM implementation of ProxyInterface.
 *
 * This class must be extended and properly mapped by the developer.
 *
 */
abstract class Proxy extends ProxyModel
{
    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $host;

    /**
     * @ORM\Column(type="integer")
     */
    protected $port;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $enable;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $lastLatency;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $failure;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $lastFailure;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $username;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $password;
}
