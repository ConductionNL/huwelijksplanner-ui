<?php

// src/Service/HuwelijkService.php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use GuzzleHttp\Client;
use Symfony\Component\Cache\Adapter\AdapterInterface as CacheInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SjabloonService
{
    private $params;
    private $cache;
    private $client;
    private $session;
    private $commonGroundService;

    public function __construct(ParameterBagInterface $params, SessionInterface $session, CacheInterface $cache, CommonGroundService $commonGroundService)
    {
        $this->params = $params;
        $this->session = $session;
        $this->cash = $cache;
        $this->commonGroundService = $commonGroundService;

        // To work with NLX we need a couple of default headers
        $this->headers = [
        		'Accept'        => 'application/ld+json',
        		'Content-Type'  => 'application/json',
        		'Authorization'  => $this->params->get('app_commonground_key'),
        		'X-NLX-Request-Application-Id' => $this->params->get('app_commonground_id')// the id of the application performing the request
        ];

        $this->client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://wrc.huwelijksplanner.online/applications/536bfb73-63a5-4719-b535-d835607b88b2/',
            // You can set any number of default request options.
        	'timeout'  => 4000.0,
        	// To work with NLX we need a couple of default headers
        	'headers' => $this->headers,
        ]);
    }

    public function getOnSlug($slug, $force = false)
    {
        $item = $this->cash->getItem('sjabloon_'.$slug);

        if ($item->isHit() && !$force) {
            //return $item->get();
        }

        
        $response = $this->commonGroundService->getResource(['component'=>'wrc','type'=>'applications','id'=>'536bfb73-63a5-4719-b535-d835607b88b2/'.$slug]);
        //$response = json_decode($response->getBody()->getContents(), true);
        $item->set($response);
        $item->expiresAt(new \DateTime('tomorrow'));
        $this->cash->save($item);

        return $response;
    }
}
