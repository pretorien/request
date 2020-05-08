<?php

namespace Pretorien\RequestBundle\Http;

use Psr\Log\LoggerAwareTrait;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\HttpClientTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Pretorien\RequestBundle\Http\Helpers\UserAgentGenerator;
use Pretorien\RequestBundle\Http\Request\PoolRequest;
use Pretorien\RequestBundle\Http\Request\Request;
use Pretorien\RequestBundle\Http\Response\PoolResponse;
use Pretorien\RequestBundle\Service\ProxyService;

class Client
{
    use HttpClientTrait;
	use LoggerAwareTrait {
		setLogger as traitLogger;
    }
    
    private $_httpClient;
    private $_proxyService;
    
    public const DEFAULT_MAX_DURATION= 5;
    public const DEFAULT_TIMEOUT= 2;
    public const DEFAULT_HTTPVERSION = CURL_HTTP_VERSION_1_1;
    public const DEFAULT_HEADERS = [];
    public const DEFAULT_PROXY = null;

    public const CLIENT_DEFAULT_OPTIONS = [
        'timeout'    => Client::DEFAULT_TIMEOUT,
        'http_version' => Client::DEFAULT_HTTPVERSION,
        'headers' => Client::DEFAULT_HEADERS,
        'proxy' => Client::DEFAULT_PROXY,
        'max_duration' => Client::DEFAULT_MAX_DURATION
    ];

    /**
     * Client constructor
     *
     * @param ProxyService $proxyService
     * @param array $options
     */
    public function __construct(ProxyService $proxyService, array $defaultOptions = [])
    {
        $this->_proxyService = $proxyService;
        $this->_httpClient = self::createClient($defaultOptions);
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->traitLogger($logger);
        $this->_httpClient->setLogger($logger);
    }

    public function getHttpClient(){
        return $this->_httpClient;
    }

    /**
     * Send pool of requests
     *
     * @param PoolRequest $poolRequest
     * @param array $options
     * @return PoolResponse
     */
    public function sendPoolRequest(
        PoolRequest $poolRequest,
        array $options = []
    ): PoolResponse {
        return PoolRequest::responses(
            $poolRequest,
            $this->_httpClient,
            $this->_proxyService
        );
    }

    /**
     * Send request
     *
     * @param Request $request
     * @param array $options
     * @return ResponseInterface
     */
    public function sendRequest(
        Request $request,
        array $options = []
    ): ResponseInterface {
        return Request::response(
            $request,
            $this->_httpClient,
            $this->_proxyService,
            $options
        );
    }

    /**
     * Create HttpClient according to the specified options
     *
     * @param array $defaultOptions     Default request's options
     * @return HttpClientInterface
     */
    public static function createClient(array $defaultOptions = [], int $maxHostConnections = 6): HttpClientInterface
    {

        $options = array_merge(
            HttpClientInterface::OPTIONS_DEFAULTS, 
            self::CLIENT_DEFAULT_OPTIONS, 
            $defaultOptions
        );

        // Dynamic options configuration
        if (
            !isset($options['headers']['user-agent']) ||
            is_null($options['headers']['user-agent'])
        ) {
            $options['headers']['user-agent'] = UserAgentGenerator::random();
        }

        return HttpClient::create($options, $maxHostConnections);
    }

    /**
     * Create BrowserKit CookieJar
     *
     * @return CookieJar
     */
    public static function createCookieJar(): CookieJar
    {
        return new CookieJar();
    }
}
