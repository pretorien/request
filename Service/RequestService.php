<?php

namespace WTeam\RequestBundle\Service;

use Symfony\Contracts\HttpClient\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use WTeam\RequestBundle\Exception\ProxyException;
use WTeam\RequestBundle\Exception\ResponseException;
use WTeam\RequestBundle\Http\Proxy;
use WTeam\RequestBundle\Http\UserAgentGenerator;

class RequestService
{
    private $logger;
    private $proxyService;
    private $configuration;

    const MAX_REQUEST_LOOP = 3;
    const MIN_REQUEST_LOOP = 1;

    const FORMAT_DEFAULT = null;
    const FORMAT_JSON = "json";
    const FORMAT_HTTPCLIENT_RESPONSE = "httpclient";

    public function __construct(LoggerInterface $logger, ProxyService $proxyService, $configuration)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->proxyService = $proxyService;
        $this->proxyService->setLogger($this->logger);
        $this->configuration = $configuration;
        return $this;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function createClient(array $options = []): HttpClientInterface
    {

        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'logger'     => new NullLogger(),
            'proxy'      => null,
            'user_agent' => UserAgentGenerator::random(),
            'cookies'    => null,
            'timeout'    => null,
            'http_version' => CURL_HTTP_VERSION_1_1
        ]);
        $resolver->setAllowedTypes('cookies', ['null', '']);
        $resolver->setAllowedTypes('user_agent', ['null', 'string']);
        $resolver->setAllowedTypes('proxy', ['null', Proxy::class]);
        $resolver->setAllowedValues('http_version', [CURL_HTTP_VERSION_1_1, CURL_HTTP_VERSION_2_0, CURL_HTTP_VERSION_1_0]);

        $options = $resolver->resolve($options);

        // Paramétrage des options
        $clientOptions = [];

        $clientOptions['headers']['user-agent'] = $options['user_agent'];

        if (!is_null($options['proxy'])) {
            $proxy = $options['proxy'];
            $clientOptions['proxy'] = (string) $proxy;
        } else {
            unset($clientOptions['proxy']);
        }

        // On positionne la configuration par défaut
        $clientOptions['verify_peer'] = false;
        $clientOptions['verify_host'] = false;
        $clientOptions['timeout'] = $options['timeout'];
        $clientOptions['http_version'] = $options['http_version'];

        // On initialise la cookie jar
        if (!is_null($options['cookies'])) {
            $clientOptions['cookies'] = $options['cookies'];
        }

        return HttpClient::create($clientOptions);
    }

    public function getMyIp(HttpClientInterface $client, ?Proxy $proxy = null)
    {
        $serviceUri = $this->configuration['myip']['uri'];
        $response = $this->request([
            "method" => "GET",
            "uri" => $serviceUri,
            "client" => $client,
            "format" => self::FORMAT_HTTPCLIENT_RESPONSE,
            "options" => [
                "proxy" => $proxy
            ]
        ]);

        $json = json_decode($response->getContent(), true);

        return [
            "ip" => $json['ip'],
            "total_time" => (int) ($response->getInfo("total_time") * 1000)
        ];
    }

    public function request($args)
    {

        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'method' => 'GET',
            'options' => [],
            'client' => null,
            'behindProxy' => true,
            'format' => self::FORMAT_DEFAULT
        ]);

        $resolver->setAllowedValues('method', ['GET', 'POST', 'PUT', 'DELETE']);
        $resolver->setAllowedTypes('options', 'array');
        $resolver->setAllowedTypes('client', ['null', HttpClientInterface::class]);
        $resolver->setAllowedTypes('behindProxy', 'boolean');
        $resolver->setAllowedTypes('format', ['null', 'string']);
        $resolver->setAllowedValues('format', [self::FORMAT_DEFAULT, self::FORMAT_JSON, self::FORMAT_HTTPCLIENT_RESPONSE]);

        $resolver->setDefined([
            'uri',
        ]);

        $resolver->setRequired('uri');

        $options = $resolver->resolve($args);

        if (!$options['behindProxy']) {
            $options['loop'] = self::MIN_REQUEST_LOOP;
        }

        if ($options['behindProxy']) {
            if (is_array($options['uri'])) {
                return $this->privatePoolRequests(
                    $options['uri'],
                    $options['options'],
                    $options['client'],
                    $options['format'],
                    1
                );
            } else {
                return $this->privateRequest(
                    $options['method'],
                    $options['uri'],
                    $options['options'],
                    $options['client'],
                    $options['format'],
                    1
                );
            }
        } else {
            if (is_array($options['uri'])) {
                return $this->publicPoolRequests(
                    $options['uri'],
                    $options['client'],
                    $options['format']
                );
            } else {
                return $this->publicRequest(
                    $options['method'],
                    $options['uri'],
                    $options['options'],
                    $options['client'],
                    $options['format']
                );
            }
        }
    }

    public function privatePoolRequests($uriList, array $options = [], $client = null, $format = null, $loop = 1)
    {

        if (is_null($client)) {
            $client = self::createClient([
                'proxy' => $this->proxyService->getRandomProxy(),
                'logger' => $this->logger
            ]);
        }

        $responses = [];
        foreach ($uriList as $uri) {
            $options = [];
            $responses[] = $client->request("GET", $uri, $options);
        }

        $result = [];
        foreach ($responses as $response) {
            try {
                $result[] = self::handleResponse($response, $format);
            } catch (ProxyException $e) {
                if ($loop <= self::MAX_REQUEST_LOOP) {
                    $this->logger->warning("RequestService::privatePoolRequests : erreur proxy => changement de proxy n°$loop");
                    $client = self::createClient([
                        'proxy' => $this->proxyService->getRandomProxy(),
                        'logger' => $this->logger
                    ]);
                    return $this->privatePoolRequests($uriList, $options, $client, $format, $loop++);
                } else {
                    $this->logger->error("RequestService::privatePoolRequests : erreur proxy => nombre de changements dépassés $loop / " . self::MAX_REQUEST_LOOP);
                    throw new $e;
                }
            } catch (\Throwable $th) {
                $this->logger->debug($th->getMessage());
            }
        }

        $this->logger->notice("privatePoolRequests:result", ["result" => $result]);
        return $result;
    }

    public function privateRequest($method, $uri = '', array $options = [], $client = null, $format = null, $loop = 1)
    {
        if (is_null($client)) {
            $client = self::createClient([
                'proxy' => $this->proxyService->getRandomProxy(),
                'logger' => $this->logger
            ]);
        }
        
        $this->logger->notice("RequestService::privateRequest : envoi d'une requête vers $method $uri", [
            'method' => $method,
            'uri' => $uri,
            'options' => $options,
            'client' => $client,
            'loop' => $loop
        ]);

        $response = $client->request($method, $uri, $options);

        try {
            $result = self::handleResponse($response, $format);
            $this->logger->info("RequestService:privateRequest", ["result" => $result]);
            return $result;
        } catch (ProxyException $e) {
            if ($loop <= self::MAX_REQUEST_LOOP) {
                $this->logger->info("RequestService::privateRequest : erreur proxy (" . $e->getMessage() . ") => changement de proxy $loop/" . self::MAX_REQUEST_LOOP);
                $client = self::createClient([
                    'proxy' => $this->proxyService->getRandomProxy(),
                    'logger' => $this->logger
                ]);
                return $this->privateRequest($method, $uri, $options, $client, $format, ++$loop);
            } else {
                $this->logger->info("RequestService::privateRequest : erreur proxy (" . $e->getMessage() . ") => changement de proxy $loop/" . self::MAX_REQUEST_LOOP);
                throw new $e;
            }
        }
        
    }

    public function publicPoolRequests($uriList, $client = null, $format = null)
    {

        if (is_null($client)) {
            $client = self::createClient([
                'proxy' => null,
                'logger' => $this->logger
            ]);
        }

        $this->logger->notice("RequestService::publicPoolRequests : envoi d'une requête vers $method $uri", [
            'list' => $uriList,
            'client' => $client,
        ]);

        $responses = [];
        foreach ($uriList as $uri) {
            $options = [];
            $responses[] = $client->request("GET", $uri, $options);
        }

        $result = [];
        foreach ($responses as $response) {
            try {
                $result[] = self::handleResponse($response, $format);
            } catch (\Throwable $th) {
                $this->logger->debug($th->getMessage());
            }
        }

        $this->logger->info("RequestService:publicPoolRequests", ["result" => $result]);
        return $result;
    }

    public function publicRequest($method, $uri = '', array $options = [], $client = null, $format = null)
    {
        if (is_null($client)) {
            $client = self::createClient([
                'proxy' => null,
                'logger' => $this->logger
            ]);
        }

        $response = $client->request($method, $uri, $options);
        $result = self::handleResponse($response, $format);
        $this->logger->info("RequestService:publicRequest", ["result" => $result]);

        return $result;
    }

    private static function handleResponse(ResponseInterface $response, $format = null)
    {
        if (200 !== $response->getStatusCode()) {
            throw self::handleException($response->getStatusCode());
        } else {
            switch ($format) {
                case self::FORMAT_JSON:
                    if (strstr($response->getHeaders()['content-type'][0], "json")) {
                        return $response->toArray();
                    } else {
                        throw new ResponseException("Le format renvoyé n'est pas du JSON");
                    }
                    break;

                case self::FORMAT_HTTPCLIENT_RESPONSE:
                    return $response;
                    break;

                case self::FORMAT_DEFAULT:
                default:
                    return new Crawler($response->getContent(), $response->getInfo("url"));
                    break;
            }
        }
    }

    private static function handleException($statusCode)
    {
        switch ($statusCode) {
            case 407:
                return new ProxyException("$statusCode : proxy Authentication Required", $statusCode);
                break;

            default:
                return new ResponseException("$statusCode : réponse incorrecte", $statusCode);
                break;
        }
    }
}
