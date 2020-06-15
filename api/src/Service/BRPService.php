<?php

// src/Service/BRPService.php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BRPService
{
    private $params;
    private $client;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;

        $this->client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://brp.zaakonline.nl/',
            // You can set any number of default request options.
            'timeout'  => 4000.0,
        ]);
    }

    public function getPersonOnBsn($bsn)
    {
        $response = $this->client->request(
            'GET',
            'https://brp.zaakonline.nl/ingeschrevenpersonen/'.$bsn,
            [
                'headers' => [
                    'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn',
                ],
            ]
        );

        $response = json_decode($response->getBody(), true);

        return $response;
    }
}
