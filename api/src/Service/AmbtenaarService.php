<?php

// src/Service/AmbtenaarService.php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\Cache\Adapter\AdapterInterface as CacheInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AmbtenaarService
{
    private $params;
    private $cache;
    private $client;
    private $commonGroundService;

    public function __construct(ParameterBagInterface $params, CommonGroundService $commonGroundService, CacheInterface $cache)
    {
        $this->params = $params;
        $this->cash = $cache;
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
        $response = $this->client->request('GET', 'groups/7f4ff7ae-ed1b-45c9-9a73-3ed06a36b9cc');
        $response = json_decode($response->getBody(), true);
        $responses = $response['_embedded']['products'];

        // Lets get the persons for ambtenaren
        //foreach($responses as $key=>$value){
        //	$responses[$key]["person"] = $this->commonGroundService->getSingle($value["persoon"]);
        //}
        return $responses;
    }
}
