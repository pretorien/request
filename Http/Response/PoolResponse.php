<?php

namespace Pretorien\RequestBundle\Http\Response;

use Symfony\Contracts\HttpClient\ResponseInterface;
use Pretorien\RequestBundle\Http\Request\Request;

class PoolResponse implements \Iterator
{
    private $_responses;
    private $_position = 0;

    public const RESPONSES_FAILED = "failed";
    public const RESPONSES_SUCCESSFUL = "successful";

    /**
     * addResponse : add response to the pool
     *
     * @param ResponseInterface $response
     * @param Request           $request
     *
     * @return self
     */
    public function addResponse(ResponseInterface $response, Request $request): self
    {
        $this->_responses[] = [
            "response" => $response,
            "request" => $request
        ];

        return $this;
    }

    /**
     * Processes responses and separates them according to their status
     *
     * @param array $options
     *
     * @return array
     */
    public function getContents(array $options = []): array
    {
        $result = [
            self::RESPONSES_SUCCESSFUL => [],
            self::RESPONSES_FAILED => []
        ];
        foreach ($this->_responses as $response) {
            try {
                $content = Response::getContent($response['response'], $options);
                $result[self::RESPONSES_SUCCESSFUL][] = self::_handleSuccessful(
                    $content,
                    $response
                );
            } catch (\Throwable $exception) {
                $result[self::RESPONSES_FAILED][] = self::_handleException(
                    $exception,
                    $response
                );
            }
        }
        return $result;
    }

    /**
     * _handleSuccessful : handle successful responses
     *
     * @param $content
     * @param array $response
     *
     * @return array
     */
    private static function _handleSuccessful($content, array $response): array
    {
        return [
            "content" => $content,
            "request" => $response['request'],
            'response' => $response['response']
        ];
    }

    /**
     * _handleException : handle unsuccesful responses
     *
     * @param $exception
     * @param array $response
     *
     * @return array
     */
    private static function _handleException($exception, array $response): array
    {
        return [
            "exception" => $exception,
            "request" => $response['request'],
            'response' => $response['response']
        ];
    }

    /**
     *
     * @return ResponseInterface
     */
    public function current(): ResponseInterface
    {
        return $this->_responses[$this->_position]["response"];
    }

    /**
     *
     * @return integer
     */
    public function key(): int
    {
        return $this->_position;
    }

    /**
     *
     * @return void
     */
    public function next(): void
    {
        ++$this->_position;
    }

    /**
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->_position = 0;
    }

    /**
     *
     * @return boolean
     */
    public function valid(): bool
    {
        return isset($this->_responses[$this->_position]["response"]);
    }
}
