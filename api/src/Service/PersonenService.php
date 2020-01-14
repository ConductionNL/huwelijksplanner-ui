<?php

// src/Service/BRPService.php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PersonenService
{
    private $params;
    private $client;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;

        $this->client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://cc.zaakonline.nl/',
            // You can set any number of default request options.
            'timeout'  => 4000.0,
        ]);
    }

    public function getAll()
    {
        $response = $this->client->request('GET', '/personen');
        $response = json_decode($response->getBody()->getContents(), true);

        return $response['hydra:member'];
    }

    public function getPersonOnBsn($bsn)
    {
        $response = $this->client->request('GET', '/personen', ['query' => ['burgerservicenummer' => $bsn]]);
        $response = json_decode($response->getBody()->getContents(), true);

        return $response['hydra:member'][0];
    }

    public function delete($persoon)
    {
        $response = $this->client->request('DELETE', '/persoon/'.$persoon['id']);
        $response = json_decode($response->getBody()->getContents(), true);

        return $response;
    }

    public function create($persoon)
    {
        $persoon['bronOrganisatie'] = 123456789;

        $response = $this->client->post('/personen', [
            RequestOptions::JSON => $persoon,
        ]);
        $response = json_decode($response->getBody()->getContents(), true);

        return $response;
    }
}
