<?php

// src/Service/HuwelijkService.php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CheckService
{
    private $params;
    private $client;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;

        $this->client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'http://demo.convenantgemeenten.nl/',
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

    public function ageCheck($bsn, datetime $date, int $age = 18)
    {
        $payload = [];
        $payload['person'] = 'ageGraph/person/nl_999999928'; //$bsn;
        $payload['validOn'] = '2018-06-28'; //date_format($date, 'Y-m-d');
        $payload['age'] = $age;
        $payload = json_encode($payload);
        $response = $this->client->request('POST', 'agetest/', $payload);
        $response = json_decode($response->getBody(), true);

        return $response;
    }
}
