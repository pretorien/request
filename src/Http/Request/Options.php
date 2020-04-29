<?php

namespace Pretorien\RequestBundle\Http\Request;

use Symfony\Component\HttpClient\HttpOptions;
use Pretorien\RequestBundle\Http\Request\Request;

class Options extends HttpOptions
{
    public const OPTIONS_DEFAULTS = [
        'auth_basic' => null,
        'auth_bearer' => null,
        'query' => [],
        'headers' => [],
        'body' => '',
        'json' => null,
        'user_data' => null,
        'max_redirects' => 20,
        'http_version' => null,
        'base_uri' => null,
        'buffer' => true,
        'on_progress' => null,
        'resolve' => [],
        'proxy' => null,
        'no_proxy' => null,
        'timeout' => null,
        'max_duration' => 0,
        'bindto' => '0',
        'verify_peer' => false,
        'verify_host' => false,
        'cafile' => null,
        'capath' => null,
        'local_cert' => null,
        'local_pk' => null,
        'passphrase' => null,
        'ciphers' => null,
        'peer_fingerprint' => null,
        'capture_peer_cert_chain' => false,
        'extra' => [],
    ];

    /**
     * Create request options
     *
     * @param array $options
     * @param array $headers
     */
    public function __construct(array $options = [], array $headers = [])
    {
        $this->options = array_merge(self::OPTIONS_DEFAULTS, $options);
        $this->setHeaders(
            array_merge(
                Request::DEFAULT_HEADERS,
                $headers,
                $options['headers'] ?? []
            )
        );
    }

    /**
     * Return request headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->options['headers'];
    }

    /**
     * Return request proxy
     *
     * @return string
     */
    public function getProxy(): string
    {
        return $this->options['proxy'];
    }
}
