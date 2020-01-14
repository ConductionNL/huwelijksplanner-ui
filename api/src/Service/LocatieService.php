<?php

// src/Service/LocatieService.php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LocatieService
{
    private $params;
    private $client;

    public function __construct(ParameterBagInterface $params, CommonGroundService $commonGroundService)
    {
        $this->params = $params;
        $this->commonGroundService = $commonGroundService;

        $this->client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'http://pdc.zaakonline.nl/',
            // You can set any number of default request options.
            'timeout'  => 4000.0,
        ]);
    }

    public function getAll()
    {
        $response = $this->client->request('GET', 'groups/170788e7-b238-4c28-8efc-97bdada02c2e');
        $response = json_decode($response->getBody(), true);
        $responses = $response['_embedded']['products'];

        // Lets get the persons for ambtenaren
        //foreach($responses as $key=>$value){
        //	$responses[$key]["person"] = $this->commonGroundService->getSingle($value["persoon"]);
        //}
        return $responses;
    }
}
