<?php

// src/Service/BRPService.php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\Cache\Adapter\AdapterInterface as CacheInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RequestTypeService
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
            'base_uri' => 'http://vtc.zaakonline.nl',
            // You can set any number of default request options.
            'timeout'  => 4000.0,
            'body'     => 'raw data',
        ]);
    }

    public function getRequestTypes($query)
    {
        $response = $this->client->request(
            'GET',
            '/request_types',
            [
                'headers' => [
                    //'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
                ],
            ]
        );

        $response = json_decode($response->getBody(), true);

        return $response['_embedded']['item'];
    }

    public function getRequestType($id, $force = false)
    {
        // In the case of linked data we might get an full url instead of just an id
        if (filter_var($id, FILTER_VALIDATE_URL)) {
            $id = basename($id);
        }

        $item = $this->cash->getItem('requesttype_'.$id);
        if ($item->isHit() && !$force) {
            return $item->get();
        }

        $response = $this->client->request(
            'GET',
            '/request_types/'.$id,
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]
        );

        $response = json_decode($response->getBody(), true);

        $item->set($response);
        $item->expiresAt(new \DateTime('tomorrow'));
        $this->cash->save($item);

        return $response;
    }

    public function getRequestTypeOnUri($uri)
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

    public function createRequestType($request)
    {
        $response = $this->client->request(
            'POST',
            '/request_types',
            [
                'json'    => $request,
                'headers' => [
                    //'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
                ],
            ]
        );

        $response = json_decode($response->getBody(), true);

        return $response;
    }

    public function updateRequestType($request)
    {
        $response = $this->client->request(
            'PUT',
            '/request_types/'.$request['id'],
            [
                'json'    => $request,
                'headers' => [
                    //'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
                ],
            ]
        );

        $response = json_decode($response->getBody(), true);

        return $response;
    }
}
