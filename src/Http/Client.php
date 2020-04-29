<?php

namespace Pretorien\RequestBundle\Http;

use Psr\Log\LoggerAwareTrait;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\HttpClientTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Pretorien\RequestBundle\Http\Helpers\UserAgentGenerator;
use Pretorien\RequestBundle\Http\Request\PoolRequest;
use Pretorien\RequestBundle\Http\Request\Request;
use Pretorien\RequestBundle\Http\Response\PoolResponse;
use Pretorien\RequestBundle\Http\Response\Response;
use Pretorien\RequestBundle\Service\ProxyService;

class Client
{
    use HttpClientTrait;
    use LoggerAwareTrait; // setLogger

    private $_httpClient;
    private $_proxyService;

    const TIMEOUT=2;
    const MAX_DURATION=5;

    /**
     * Client constructor
     *
     * @param ProxyService $proxyService
     * @param array $options
     */
    public function __construct(ProxyService $proxyService, array $options = [])
    {
        $this->_proxyService = $proxyService;
        $this->_httpClient = self::createClient([]);
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
     * @param array $options
     * @return HttpClientInterface
     */
    public static function createClient(array $options = []): HttpClientInterface
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            [
                'user_agent' => UserAgentGenerator::random(),
                'cookies'    => false,
                'timeout'    => Client::TIMEOUT,
                'http_version' => CURL_HTTP_VERSION_1_1,
                'headers' => [],
                'proxy' => null,
                'max_duration' => Client::MAX_DURATION
            ]
        );
        $resolver->setAllowedTypes('cookies', 'bool');
        $resolver->setAllowedTypes('user_agent', ['null', 'string']);
        $resolver->setAllowedValues(
            'http_version',
            [
                CURL_HTTP_VERSION_1_1,
                CURL_HTTP_VERSION_2_0,
                CURL_HTTP_VERSION_1_0
            ]
        );

        $options = $resolver->resolve($options);

        // Paramétrage des options
        $clientOptions['headers'] = $options['headers'];
        $clientOptions['headers']['user-agent'] = $options['user_agent'];
        // if($options['cookies']){
        //     $clientOptions['headers']['cookies'] = self::createCookieJar();
        // }

        // On positionne la configuration par défaut
        $clientOptions['verify_peer'] = false;
        $clientOptions['verify_host'] = false;
        $clientOptions['timeout'] = $options['timeout'];
        $clientOptions['http_version'] = $options['http_version'];

        return HttpClient::create($clientOptions);
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
