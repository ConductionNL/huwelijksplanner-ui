<?php

// src/Service/BRPService.php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EmployeeService
{
    private $params;
    private $client;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;

        $this->client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'http://mrc.zaakonline.nl/',
            // You can set any number of default request options.
            'timeout'  => 4000.0,
            'body'     => 'raw data',
        ]);
    }

    public function getEmployees($query)
    {
        $response = $this->client->request(
            'GET',
            '/employees',
            [
                'headers' => [
                    //'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
                ],
            ]
        );

        $response = json_decode($response->getBody(), true);

        return $response['_embedded']['item'];
    }

    public function getEmployee($id)
    {
        $response = $this->client->request(
            'GET',
            '/employees/'.$id,
            [
                'headers' => [
                    //'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
                ],
            ]
        );

        $response = json_decode($response->getBody(), true);

        return $response;
    }

    public function getEmployeeOnUri($uri)
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

    public function createEmployee($employee)
    {
        $response = $this->client->request(
            'POST',
            '/employees',
            [
                'json'    => $employee,
                'headers' => [
                    //'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
                ],
            ]
        );

        $response = json_decode($response->getBody(), true);

        return $response;
    }

    public function updateEmployee($employee)
    {
        $response = $this->client->request(
            'PUT',
            '/employees/'.$contact['id'],
            [
                'json'    => $employee,
                'headers' => [
                    //'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
                ],
            ]
        );

        $response = json_decode($response->getBody(), true);

        return $response;
    }
}
