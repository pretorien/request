<?php

namespace Pretorien\RequestBundle\Http\Request;

class PublicRequest extends Request
{
    /**
     * Public request constructor
     *
     * @param string $url
     * @param string $method
     * @param array  $options
     */
    public function __construct(
        string $url,
        string $method = self::METHOD_GET,
        array $options = []
    ) {
        parent::__construct($url, $method, $options);
        $this->hasProxy = false;
        return $this;
    }
}
