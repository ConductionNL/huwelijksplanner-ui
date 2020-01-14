<?php

// src/Service/HuwelijkService.php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class BerichtenService
{
    private $params;
    private $client;
    private $session;

    public function __construct(ParameterBagInterface $params, SessionInterface $session)
    {
        $this->params = $params;
        $this->session = $session;

        $this->client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'http://api.zaakonline.nl/producten',
            // You can set any number of default request options.
            'timeout'  => 4000.0,
        ]);
    }

    public function getAll()
    {
        $response = $this->client->request('GET');
        $response = json_decode($response->getBody(), true);

        return $response['hydra:member'];
    }

    public function getOne($id)
    {
        $response = $this->client->request('GET', '/producten/'.$id);
        $response = json_decode($response->getBody(), true);

        return $response;
    }

    public function save($bericht)
    {
        if ($ambtenaar['id']) {
            $response = $this->client->put('ambtenaar/'.$ambtenaar['id'], [
                \GuzzleHttp\RequestOptions::JSON => $ambtenaar,
            ]);
        } else {
            $response = $this->client->post('ambtenaren', [
                \GuzzleHttp\RequestOptions::JSON => $ambtenaar,
            ]);
        }

        //$response=  $this->client->send($request);
        $response = json_decode($response->getBody(), true);

        return $response;
    }
}
