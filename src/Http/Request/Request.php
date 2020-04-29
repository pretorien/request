<?php

namespace Pretorien\RequestBundle\Http\Request;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Pretorien\RequestBundle\Http\Helpers\UserAgentGenerator;
use Pretorien\RequestBundle\Http\Request\Options;
use Pretorien\RequestBundle\Service\ProxyService;

/**
 * Request class
 */
class Request
{
    private $_method;
    private $_url;
    private $_options;
    public $hasProxy;
    public $proxy;
        
    const METHOD_GET = "GET";
    const METHOD_POST = "POST";
    const TYPE_PUBLIC = "public";
    const TYPE_PRIVATE = "private";

    public const DEFAULT_HEADERS = [
        'Content-Encoding' => 'deflate, gzip',
        'Accept-Language' => 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
        'Cache-Control' => 'no-cache',
        'DNT' => 1,
        'Sec-Fetch-Dest' => 'empty',
        'Sec-Fetch-Mode' => 'cors',
        'Sec-Fetch-Site' => 'cross-site',
    ];

    /**
     * Request constructor
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
        $this->_options = new Options($options, $this->_getDefaultHeaders());
        $this->hasProxy = true;
        $this->proxy = null;
        $this->_url = $url;
        $this->_method = $method;
        return $this;
    }

    /**
     * _getDefaultHeaders : return generated default headers
     *
     * @return array
     */
    private function _getDefaultHeaders(): array
    {
        return array_merge(
            self::DEFAULT_HEADERS,
            [
                'User-Agent' => UserAgentGenerator::random()
            ]
        );
    }

    /**
     * response : return request response
     *
     * @param Request             $request
     * @param HttpClientInterface $client
     * @param ProxyService        $proxyService
     *
     * @return ResponseInterface
     */
    public static function response(
        Request $request,
        HttpClientInterface $client,
        ProxyService $proxyService
    ): ResponseInterface {
        $request = self::_prepare($request, $proxyService);
        return $client->request(
            $request->getMethod(),
            $request->getUrl(),
            $request->getOptions()->toArray()
        );
    }

    /**
     * factory : create request according to the given type
     *
     * @param string $url
     * @param string $type
     * @param string $method
     * @param array  $options
     *
     * @return self
     */
    public static function factory(
        string $url,
        string $type = self::TYPE_PUBLIC,
        string $method = self::METHOD_GET,
        array $options = []
    ): self {
        switch ($type) {
        case Request::TYPE_PUBLIC:
            return new PublicRequest($url, $method, $options);
            break;
        case Request::TYPE_PRIVATE:
        default:
            return new PrivateRequest($url, $method, $options);
            break;
        }
    }

    /**
     * _prepare : set request generated options
     *
     * @param Request      $request
     * @param ProxyService $proxyService
     *
     * @return Request
     */
    private static function _prepare(
        Request $request,
        ProxyService $proxyService
    ): Request {
        if ($request->hasProxy) {
            if (is_null($request->proxy)) {
                $proxy = $proxyService->getRandomProxy()->__toString();
            } else {
                $proxy = $request->proxy;
            }
        } else {
            $proxy = "";
        }
        $request->getOptions()->setProxy($proxy);
        return $request;
    }
 
    /**
     * Get the value of method
     */
    public function getMethod()
    {
        return $this->_method;
    }

    /**
     * Set the value of method
     *
     * @return self
     */
    public function setMethod($method): self
    {
        $this->_method = $method;

        return $this;
    }

    /**
     * Get the value of url
     */
    public function getUrl(): string
    {
        return $this->_url;
    }

    /**
     * Set the value of url
     *
     * @return self
     */
    public function setUrl($url): self
    {
        $this->_url = $url;

        return $this;
    }

    /**
     * Get the value of options
     */
    public function getOptions(): ?Options
    {
        return $this->_options;
    }

    /**
     * Get the value of headers
     */
    public function getHeaders()
    {
        return $this->getOptions()->getHeaders();
    }

    public function getProxy()
    {
        $options = $this->getOptions()->toArray();
        if (isset($options['proxy'])) {
            return \parse_url($options['proxy']);
        } else {
            return null;
        }
    }

    /**
     * Set the value of headers
     *
     * @return self
     */
    public function setHeaders($headers): self
    {
        $this->getOptions()->setHeaders($headers);
        return $this;
    }

    public function setHeader($name, $value): self
    {
        $headers = array_merge($this->getHeaders(), [$name => $value]);
        $this->setHeaders($headers);
        return $this;
    }
}
