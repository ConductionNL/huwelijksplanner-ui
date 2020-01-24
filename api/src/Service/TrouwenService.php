<?php

// src/Service/HuwelijkService.php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TrouwenService
{
    private $params;
    private $client;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;

        $this->client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'http://api.zaakonline.nl/ambtenaren',
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
        $response = $this->client->request('GET', 'ambtenaar/'.$id);
        $response = json_decode($response->getBody(), true);

        return $response;
    }

    public function save($ambtenaar)
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
