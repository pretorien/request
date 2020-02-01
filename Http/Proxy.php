<?php

namespace WTeam\RequestBundle\Http;

use WTeam\RequestBundle\Entity\Proxy as EntityProxy;

class Proxy
{
    private $host;
    private $port;
    private $username;
    private $password;

    public static function toPrototype(EntityProxy $entityProxy): Proxy
    {
        return new Proxy($entityProxy->getHost(), $entityProxy->getPort(), $entityProxy->getUsername(), $entityProxy->getPassword());
    }

    public static function toEntity(Proxy $proxy): EntityProxy
    {
        return new EntityProxy($proxy->getHost(), $proxy->getPort(), $proxy->getUsername(), $proxy->getPassword());
    }

    public function __construct($host, $port, $username = null, $password = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }

    public function __toString()
    {
        if (!empty($this->getUsername()) && !empty($this->getPassword())) {
            return "http://" . rawurlencode($this->getUsername()) . ":" . rawurlencode($this->getPassword()) . "@" . $this->gethost() . ":" . $this->getPort();
        } else {
            return "http://" . $this->gethost() . ":" . $this->getPort();
        }
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function setHost(string $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setPort(int $port): self
    {
        $this->port = $port;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getUserPassword(): ?string
    {
        return $this->getUsername() . ':' . $this->getPassword();
    }
  
}
