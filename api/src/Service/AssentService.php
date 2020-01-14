<?php

// src/Service/BRPService.php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\Cache\Adapter\AdapterInterface as CacheInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AssentService
{
    private $params;
    private $cache;
    private $brpService;
    private $contactService;
    private $client;

    public function __construct(ParameterBagInterface $params, BRPService $brpService, ContactService $contactService, CacheInterface $cache)
    {
        $this->params = $params;
        $this->cash = $cache;
        $this->brpService = $brpService;
        $this->contactService = $contactService;

        $this->client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://irc.zaakonline.nl',
            // You can set any number of default request options.
            'timeout'  => 4000.0,
            'body'     => 'raw data',
        ]);
    }

    public function getAssent($id, $force = false)
    {
        $item = $this->cash->getItem('assent'.$id);
        if ($item->isHit() && !$force) {
            return $item->get();
        }

        $response = $this->client->request('GET', '/assents/'.$id, [
            'headers' => [
                //'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
            ],
        ]
            );

        $response = json_decode($response->getBody(), true);

        $item->set($response);
        $item->expiresAt(new \DateTime('tomorrow'));
        $this->cash->save($item);

        return $response;
    }

    public function getAssentOnUri($uri)
    {
        // If a / has been supplied then we need to remove that first
        $uri = ltrim($uri, '/');

        $response = $this->client->request('GET', $uri, [
            'headers' => [
                //'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
            ],
        ]
            );

        $response = json_decode($response->getBody(), true);

        if ($response['contact']) {
            $response['contactObject'] = $this->contactService->getContactOnUri($response['contact']);
        }
        if ($response['person']) {
            $response['personObject'] = $this->brpService->getPersonOnBsn($response['person']);
        }

        return $response;
    }

    public function createAssent($assent)
    {
        $response = $this->client->request('POST', '/assents', [
            'json'    => $assent,
            'headers' => [
                //'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
            ],
        ]
        );

        $response = json_decode($response->getBody(), true);

        return $response;
    }

    public function updateAssent($assent)
    {
        $response = $this->client->request('PUT', '/assents/'.$assent['id'], [
            'json'    => $assent,
            'headers' => [
                //'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
            ],
        ]
        );

        $response = json_decode($response->getBody(), true);

        return $response;
    }
}
