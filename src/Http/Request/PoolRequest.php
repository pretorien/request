<?php

namespace Pretorien\RequestBundle\Http\Request;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Pretorien\RequestBundle\Service\ProxyService;
use Pretorien\RequestBundle\Http\Request\Request;
use Pretorien\RequestBundle\Http\Response\PoolResponse;

class PoolRequest implements \Iterator
{
    private $_requests;
    private $_position = 0;
    private $_options;

    /**
     * PoolRequest constructor
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->_position = 0;
        $this->_options = $options;
    }

    /**
     * addRequest : add request to the pool
     *
     * @param Request $request
     *
     * @return self
     */
    public function addRequest(Request $request): self
    {
        $this->_requests[] = $request;
        return $this;
    }

    /**
     * responses
     *
     * @param PoolRequest         $poolRequest
     * @param HttpClientInterface $client
     * @param ProxyService        $proxyService
     *
     * @return PoolResponse
     */
    public static function responses(
        PoolRequest $poolRequest,
        HttpClientInterface $client,
        ProxyService $proxyService
    ): PoolResponse {
        $poolResponse = new PoolResponse();
        foreach ($poolRequest as $request) {
            $poolResponse->addResponse(
                Request::response(
                    $request,
                    $client,
                    $proxyService
                ),
                $request
            );
        }
        return $poolResponse;
    }

    /**
     * getRequests
     *
     * @return array
     */
    public function getRequests(): array
    {
        return $this->_requests;
    }

    /**
     * current
     *
     * @return Request
     */
    public function current(): Request
    {
        return $this->_requests[$this->_position];
    }

    /**
     * key
     *
     * @return integer
     */
    public function key(): int
    {
        return $this->_position;
    }

    /**
     * next
     *
     * @return void
     */
    public function next(): void
    {
        ++$this->_position;
    }

    /**
     * rewind
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->_position = 0;
    }

    /**
     * valid
     *
     * @return boolean
     */
    public function valid(): bool
    {
        return isset($this->_requests[$this->_position]);
    }
}
