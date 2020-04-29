<?php

namespace Pretorien\RequestBundle\Model;

class Proxy
{
    protected $host;
    protected $port;
    protected $enable;
    protected $lastLatency;
    protected $failure;
    protected $username;
    protected $password;
    protected $lastFailure;

    public function __toString()
    {
        if (!empty($this->getUsername()) && !empty($this->getPassword())) {
            return
                "http://" . rawurlencode($this->getUsername()) .
                ":" . rawurlencode($this->getPassword()) .
                "@" . $this->gethost() . ":" . $this->getPort();
        } else {
            return "http://" . $this->gethost() . ":" . $this->getPort();
        }
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function setHost(string $host)
    {
        $this->host = $host;

        return $this;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setPort(int $port)
    {
        $this->port = $port;

        return $this;
    }

    public function getEnable(): ?bool
    {
        return $this->enable;
    }

    public function setEnable(bool $enable)
    {
        $this->enable = $enable;

        return $this;
    }

    public function getLastLatency(): ?int
    {
        return $this->lastLatency;
    }

    public function setLastLatency(?int $lastLatency)
    {
        $this->lastLatency = $lastLatency;

        return $this;
    }

    public function getFailure(): ?int
    {
        return $this->failure;
    }

    public function setFailure(?int $failure)
    {
        $this->failure = $failure;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username)
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password)
    {
        $this->password = $password;

        return $this;
    }

    public function getUserPassword(): ?string
    {
        return $this->getUsername() . ':' . $this->getPassword();
    }

    public function getLastFailure(): ?\DateTimeInterface
    {
        return $this->lastFailure;
    }

    public function setLastFailure(?\DateTimeInterface $lastFailure)
    {
        $this->lastFailure = $lastFailure;

        return $this;
    }
}
