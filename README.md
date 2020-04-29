# Request bundle

This repository contains Request bundle which helps you to send requests in a public way or behind a proxy.
Several commands allow you to fetching and checking proxies.

> This bundle based on [Symfony HttpClient Component](https://symfony.com/doc/current/components/http_client.html)

## Installation

Installation using composer :

```bash
   composer require pretorien/request
```

Then, enable the bundle by adding it to the list of registered bundles in the `config/bundles.php` file of your project:

```php
<?php

return [
    // ...
    Pretorien\RequestBundle\RequestBundle::class => ['all' => true],
];
```

It's necessary to create a Proxy entity extending the bundle one.

```php
<?php
// src/Entity/Proxy.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Pretorien\RequestBundle\Entity\Proxy as BaseProxy;

/**
 * @ORM\Entity
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class Proxy extends BaseProxy
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
}
```

Finally, you must update your database :

```bash
    php bin/console doctrine:schema:update --force
```

## Configuration

```yaml
request:
    proxy:
        nordvpn:
            username:             ~
            password:             ~
            api:                  'https://api.nordvpn.com/v1/servers/recommendations?filters\[servers_groups\]=5&filters\[servers_technologies\]=9&filters\[country_id\]=74'
    class:
        model:
            proxy: App\Entity\Proxy
```

## Usage

This bundle provides several commands under the pretorien:proxy: namespace. List them all executing this command:

```sh
php bin/console list pretorien:proxy

pretorien:proxy:check  Check proxies latency
pretorien:proxy:fetch  Fetch proxies
pretorien:proxy:list   List proxies
```

### Fetching Proxies

First of all, you must initialize the proxy list with the `pretorien:proxy:fetch` command.
This is based on fetchers allowing to retrieve data from provides. Currently there is only one : NordVPN.
You must therefore configure your cretendials as indicated above.

This command has several options :

```sh
      --fetcher=FETCHER            Which provider do you want to use? [default: ["nordvpn"]] (multiple values allowed)
      --force-check                Force latency check after fetching
      --renew                      Delete old proxies before fetching
      --drop-failed[=DROP-FAILED]  Drop proxies with more than failures ? [default: 10]
```

### Checking Latency

The `pretorien:proxy:check` command checks existing proxies health and checks if these are anonymous.

## Making Requests

RequestService contains all the methods allowing to sending private or public requests.
You can use this service directly in your controllers via Autowiring :

```php
    use Pretorien\RequestBundle\Service\RequestService;

    /* ... */

    public function myFunction(RequestService $requestService)
    {
        /* ... */
    }
```

### Sending public and private Requests

RequestService provides a single request() method to perform all kinds of HTTP requests.
All private requests are automatically configured with a proxy.

```php
    use Pretorien\RequestBundle\Service\RequestService;
    use Pretorien\RequestBundle\Http\Request\Request;

    /* ... */

    public function myFunction(RequestService $requestService)
    {
        $url = "http://www.example.com";
        $options = [];
        $response = $requestService->request($url, Request::METHOD_GET, Request::TYPE_PUBLIC, $options);
        $response = $requestService->request($url, Request::METHOD_POST, Request::TYPE_PUBLIC, $options);
        $response = $requestService->request($url, Request::METHOD_GET, Request::TYPE_PRIVATE, $options);
        $response = $requestService->request($url, Request::METHOD_POST, Request::TYPE_PRIVATE, $options);
        /* ... */
    }
```

Two aliases make it easier to send requests

```php
    use Pretorien\RequestBundle\Service\RequestService;
    use Pretorien\RequestBundle\Http\Request\Request;

    /* ... */

    public function myFunction(RequestService $requestService)
    {
        $url = "http://www.example.com";
        $options = [];
        $response = $requestService->publicRequest($url, Request::METHOD_GET, $options);
        $response = $requestService->privateRequest($url, Request::METHOD_GET, $options);
        /* ... */
    }
```

These methods return an [ResponseInterface](https://symfony.com/doc/current/components/http_client.html#processing-responses) object.
Responses are always asynchronous, so that the call to the method returns immediately
instead of waiting to receive the response:

```php
    use Pretorien\RequestBundle\Service\RequestService;

    /* ... */

    public function myFunction(RequestService $requestService)
    {
        $url = "http://www.example.com";
        $response = $requestService->privateRequest($url, [$method, $options]);
        // getting the response headers waits until they arrive
        $contentType = $response->getHeaders()['content-type'][0];

        // trying to get the response contents will block the execution until
        // the full response contents are received
        $contents = $response->getContent();
    }
```

### Sending concurrent Requests

Many requests can be sent simultaneously thanks to PoolRequest.
RequestService provides a single createPoolRequest() method to create a new PoolRequest object having an addRequest method. This allows you to add requests (private or public) to the pool.

Finally, the sendPoolRequest method will allow you to send this pool.

#### Creating Pool Request

```php
    use Pretorien\RequestBundle\Service\RequestService;
    use Pretorien\RequestBundle\Http\Response\PoolResponse;
    use Pretorien\RequestBundle\Http\Request\PrivateRequest;
    use Pretorien\RequestBundle\Http\Request\PublicRequest;

    /* ... */

    public function myFunction(RequestService $requestService)
    {
        $url = "http://www.example.com";
        $pool = $requestService->createPoolRequest();
        $privateRequest = new PrivateRequest($url);
        $publicRequest = new PublicRequest($url);

        $pool->addRequest($privateRequest);
        $pool->addRequest($publicRequest);

        $poolResponse = $requestService->sendPoolRequest($pool);
    }
```

#### Processing Responses

The response returned by sendPoolRequest method is an object of type PoolResponse which is iterable and provide [ResponseInterface](https://symfony.com/doc/current/components/http_client.html#processing-responses) object.  

```php
    use Pretorien\RequestBundle\Service\RequestService;
    use Pretorien\RequestBundle\Http\Response\PoolResponse;
    use Pretorien\RequestBundle\Http\Request\PrivateRequest;
    use Pretorien\RequestBundle\Http\Request\PublicRequest;

    /* ... */

    public function myFunction(RequestService $requestService)
    {
        $url = "http://www.example.com";
        $pool = $requestService->createPoolRequest();
        $privateRequest = new PrivateRequest($url);
        $publicRequest = new PublicRequest($url);

        $pool->addRequest($privateRequest);
        $pool->addRequest($publicRequest);

        $poolResponse = $requestService->sendPoolRequest($pool);
        $responses = $poolResponse->getContents();

        foreach ($responses[PoolResponse::RESPONSES_SUCCESSFUL] as $response) {
            $content = $response['content'];
            $request = $response['request'];
            $httpClientResponse = $response['response'];
        }

        foreach ($responses[PoolResponse::RESPONSES_FAILED] as $response) {
            $exception = $response['exception'];
            $request = $response['request'];
            $httpClientResponse = $response['response'];
        }
    }
```

## License and contributors

Published under the MIT, read the [LICENSE](LICENSE) file for more information.