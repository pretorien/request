<?php

namespace  Pretorien\RequestBundle\Exception;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ResponseException implements TransportExceptionInterface
{
    public $statusCode;

    public function __construct(
        string $message = "",
        int $code = 0,
        \Throwable $previous = null
    ) {
        $this->setStatusCode($code);
        parent::__construct($message, $code, $previous);
    }

    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
