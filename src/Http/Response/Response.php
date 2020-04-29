<?php

namespace Pretorien\RequestBundle\Http\Response;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class Response
{
    public const FORMAT_DEFAULT = null;
    public const FORMAT_JSON = "json";
    public const FORMAT_CRAWLER = "crawler";
    public const FORMAT_RESPONSE_INTERFACE = "response";

    /**
     * processes responses according to the desired return format
     *
     * @param ResponseInterface $response
     * @param array             $options
     *
     * @return void
     */
    public static function getContent(
        ResponseInterface $response,
        array $options = []
    ) {
        $resolver = self::configureOptions();
        $options = $resolver->resolve($options);
       
        try {
            return self::_formatResponse($response, $options["format"]);
        } catch (TransportExceptionInterface $e) {
            if ($options['throw']) {
                throw $e;
            }
        } catch (\Throwable $th) {
            $response->cancel();
            if ($options['throw']) {
                throw $th;
            }
        }
    }

    /**
     *
     * @return OptionsResolver
     */
    public static function configureOptions(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setDefault('format', self::FORMAT_DEFAULT);
        $resolver->setAllowedValues(
            'format',
            [
                self::FORMAT_DEFAULT,
                self::FORMAT_CRAWLER,
                self::FORMAT_JSON,
                self::FORMAT_RESPONSE_INTERFACE
            ]
        );

        $resolver->setDefault('throw', true);
        $resolver->setAllowedTypes('throw', 'boolean');

        $resolver->setDefault('failback', 0);

        return $resolver;
    }

    /**
     * Format response according to the specified format
     *
     * @param ResponseInterface $response
     * @param string            $format
     *
     * @return void
     */
    private static function _formatResponse(
        ResponseInterface $response,
        string $format = self::FORMAT_DEFAULT
    ) {
        switch ($format) {
        case self::FORMAT_JSON:
            if (strstr($response->getHeaders()['content-type'][0], "json")) {
                return $response->toArray();
            } else {
                throw new \Exception("Le format renvoyé n'est pas du JSON");
            }
            break;

        case self::FORMAT_CRAWLER:
            return new Crawler($response->getContent(), $response->getInfo("url"));
            break;

        case self::FORMAT_RESPONSE_INTERFACE:
            return $response;
            break;

        default:
        case self::FORMAT_DEFAULT:
            return $response->getContent();
            break;
        }
    }
}
