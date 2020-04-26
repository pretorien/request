# Request bundle

This repository contains Request bundle which helps you to send requests in a public way or behind a proxy.
Several commands allow you to fetching and checking proxies.

## Installation with Composer

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

Finally, you can update your database :

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

This bundle provides several commands under the make: namespace. List them all executing this command:

```sh
php bin/console list pretorien:proxy

pretorien:proxy:check  Check proxies latency
pretorien:proxy:fetch  Fetch proxies
pretorien:proxy:list   List proxies
```

### Send request

```php
    use Pretorien\RequestBundle\Service\RequestService;

    /* ... */

    public function myFunction(RequestService $requestService)
    {
        $url = "http://www.example.com";
        $request = $requestService->publicRequest($url, [$method, $options]);
        $content = $request->getContent();
    }
```

```php
    use Pretorien\RequestBundle\Service\RequestService;

    /* ... */

    public function myFunction(RequestService $requestService)
    {
        $url = "http://www.example.com";
        $request = $requestService->privateRequest($url, [$method, $options]);
        $content = $request->getContent();
    }
```

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
        $responses = $poolResponse->getContents(['throw' => false]);

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