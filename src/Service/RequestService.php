<?php

namespace Pretorien\RequestBundle\Service;

use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Pretorien\RequestBundle\Http\Client;
use Pretorien\RequestBundle\Http\Request\PoolRequest;
use Pretorien\RequestBundle\Http\Request\Request;
use Pretorien\RequestBundle\Http\Response\PoolResponse;

/**
 * RequestService
 */
class RequestService
{
    private $_proxyService;
    private $_configuration;

    const MAX_REQUEST_LOOP = 3;
    const MIN_REQUEST_LOOP = 1;

    /**
     * RequestService constructor
     *
     * @param ProxyService $proxyService
     * @param array        $configuration
     */
    public function __construct(ProxyService $proxyService, $configuration)
    {
        $this->_proxyService = $proxyService;
        $this->_configuration = $configuration;
        return $this;
    }

    /**
     * Create Client
     *
     * @param array $options
     *
     * @return Client
     */
    public function createClient(array $options = []): Client
    {
        if (\is_null($options['client'])) {
            return new Client($this->_proxyService, $options['options']);
        } else {
            return $options['client'];
        }
    }

    /**
     * Send private request
     *
     * @param string $url
     * @param string $method
     * @param array  $options
     *
     * @return ResponseInterface
     */
    public function privateRequest(
        string $url,
        string $method = Request::METHOD_GET,
        array $options = []
    ): ResponseInterface {
        return $this->request($url, $method, Request::TYPE_PRIVATE, $options);
    }

    /**
     * Send public request
     *
     * @param string $url
     * @param string $method
     * @param array  $options
     *
     * @return ResponseInterface
     */
    public function publicRequest(
        string $url,
        string $method = Request::METHOD_GET,
        array $options = []
    ): ResponseInterface {
        return $this->request($url, $method, Request::TYPE_PUBLIC, $options);
    }
    
    /**
     * Send request
     *
     * @param string $url
     * @param string $method
     * @param string $type
     * @param array  $options
     *
     * @return ResponseInterface
     */
    public function request(
        string $url,
        string $method = Request::METHOD_GET,
        string $type = Request::TYPE_PUBLIC,
        array $options = []
    ): ResponseInterface {
        $resolver = self::_configureRequestOptions();
        $options = $resolver->resolve($options);

        $client = $this->createClient($options);
        $request = Request::factory($url, $type, $method, $options['options']);

        return $client->sendRequest($request);
    }

    /**
     * Configure request options
     *
     * @return OptionsResolver
     */
    private static function _configureRequestOptions(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            [
                'options' => [],
                'client' => null,
            ]
        );

        $resolver->setAllowedTypes('client', ["null", Client::class]);
        $resolver->setAllowedTypes('options', 'array');

        return $resolver;
    }

    /**
     * Create PoolRequest
     *
     * @param array $options
     *
     * @return PoolRequest
     */
    public function createPoolRequest(array $options = []): PoolRequest
    {
        return new PoolRequest($options);
    }

    /**
     * Send PoolRequest
     *
     * @param PoolRequest $poolRequest
     * @param array       $options
     *
     * @return PoolResponse
     */
    public function sendPoolRequest(
        PoolRequest $poolRequest,
        array $options = []
    ): PoolResponse {
        $resolver = self::_configureRequestOptions();
        $options = $resolver->resolve($options);

        $client = $this->createClient($options);
        return $client->sendPoolRequest($poolRequest);
    }
}
