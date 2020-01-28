<?php

// src/Service/HuwelijkService.php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use App\Service\CommonGroundService;
use App\Service\BRPService;

class HuwelijkService
{
    private $params;
    private $client;
    private $session;
    private $commonGroundService;

    public function __construct(ParameterBagInterface $params, SessionInterface $session, CommonGroundService $commonGroundService)
    {
        $this->params = $params;
        $this->session = $session;
        $this->commonGroundService = $commonGroundService;

        $this->client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'http://trouwen.demo.zaakonline.nl/huwelijken',
            // You can set any number of default request options.
            'timeout'  => 4000.0,
        ]);
    }

    public function requestToOrder($request)
    {
        $orderItems = [];

        foreach ($request['properties'] as $property) {

            // Is the property an array
            if (is_array($property)) {
                foreach ($property as $item) {
                    if (strpos($item, 'https://pdc.zaakonline.nl/products/') !== false) {
                        $object = $this->commonGroundService->getResource($property);
                        $orderItems[] = ['quantity'=>1, 'product'=>$item, 'name'=>$object['name'], 'price'=>$object['price'], 'curency'=>$object['curency']];
                    }
                }
            } else {
                if (strpos($property, 'https://pdc.zaakonline.nl/products/') !== false) {
                    $object = $this->commonGroundService->getResource($property);
                    $orderItems[] = ['quantity'=>1, 'product'=>$property, 'name'=>$object['name'], 'price'=>$object['price'], 'curency'=>$object['curency']];
                }
            }
        }
    }

    public function orderToInvoice($order)
    {
    }
    
    public function login(string $bsn)
    {    	
    	/* @todo eigenlijk moeten brp calls via de commonground service */
    	if($bsn && $persoon = $brpService->getPersonOnBsn($bsn)){
    		$this->session-> set('user', $persoon);
    	}    
    	
    	return $persoon;
    }
    
    

}
