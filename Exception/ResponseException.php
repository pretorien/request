<?php

namespace  WTeam\RequestBundle\Exception;

class ResponseException extends \Exception
{

    public $statusCode;

    public function __construct(string $message = "", int $code = 0, \Throwable $previous = NULL)
    {
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
