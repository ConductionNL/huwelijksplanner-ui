<?php

// src/Service/BRPService.php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\Cache\Adapter\AdapterInterface as CacheInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PdcService
{
    private $params;
    private $cache;
    private $client;

    public function __construct(ParameterBagInterface $params, CacheInterface $cache)
    {
        $this->params = $params;
        $this->cash = $cache;

        $this->client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'http://pdc.zaakonline.nl/',
            // You can set any number of default request options.
            'timeout'  => 4000.0,
            'body'     => 'raw data',
        ]);
    }

    public function getProducts($query = [])
    {
        $response = $this->client->request(
            'GET',
            '/products',
            [
                'headers' => ['Accept' => 'application/json'],
                'query'   => $query,
            ]
        );

        $response = json_decode($response->getBody(), true);

        return $response;
    }

    public function getProduct($id)
    {
        $response = $this->client->request(
            'GET',
            '/products/'.$id,
            [
                'headers' => [
                    //'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
                ],
            ]
        );

        $response = json_decode($response->getBody(), true);

        return $response;
    }

    public function getGroups($query)
    {
        $response = $this->client->request(
            'GET',
            '/groups',
            [
                'headers' => [
                    //'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
                ],
            ]
        );

        $response = json_decode($response->getBody(), true);

        return $response['_embedded']['item'];
    }

    public function getGroup($id)
    {
        $response = $this->client->request(
            'GET',
            '/groups/'.$id,
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]
        );

        $response = json_decode($response->getBody(), true);

        return $response;
    }

    public function getProductOnUri($uri)
    {
        $response = $this->client->request(
            'GET',
            $uri,
            [
                'headers' => [
                    //'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
                ],
            ]
        );

        $response = json_decode($response->getBody(), true);

        return $response;
    }

    public function createProduct($product)
    {
        $response = $this->client->request(
            'POST',
            '/products',
            [
                'json'    => $product,
                'headers' => [
                    //'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
                ],
            ]
        );

        $response = json_decode($response->getBody(), true);

        return $response;
    }

    public function updateProduct($product)
    {
        $response = $this->client->request(
            'PUT',
            '/products/'.$product['id'],
            [
                'json'    => $product,
                'headers' => [
                    //'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
                ],
            ]
        );

        $response = json_decode($response->getBody(), true);

        return $response;
    }

    public function createGroup($group)
    {
        $response = $this->client->request(
            'POST',
            '/groups',
            [
                'json'    => $group,
                'headers' => [
                    //'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
                ],
            ]
        );

        $response = json_decode($response->getBody(), true);

        return $response;
    }

    public function updateGroup($group)
    {
        $response = $this->client->request(
            'PUT',
            '/groups/'.$group['id'],
            [
                'json'    => $group,
                'headers' => [
                    //'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
                ],
            ]
        );

        $response = json_decode($response->getBody(), true);

        return $response;
    }
}
