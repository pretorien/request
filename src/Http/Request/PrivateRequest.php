<?php

namespace Pretorien\RequestBundle\Http\Request;

use Pretorien\RequestBundle\Model\Proxy;

class PrivateRequest extends Request
{
    public const DEFAULT_HEADERS = [
        'Content-Encoding' => 'deflate, gzip'
    ];

    /**
     * Private request constructor
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
        $this->hasProxy = true;
        $this->proxy = null;
        return $this;
    }

    public function setProxy(Proxy $proxy)
    {
        $this->proxy = $proxy;
        return $this;
    }
}
