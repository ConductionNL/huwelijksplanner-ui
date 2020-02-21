<?php

// src/Service/HuwelijkService.php

namespace App\Service;

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

    public function __construct(ParameterBagInterface $params, SessionInterface $session, CacheInterface $cache)
    {
        $this->params = $params;
        $this->session = $session;
        $this->cash = $cache;

        $this->client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://wrc.zaakonline.nl/applications/536bfb73-63a5-4719-b535-d835607b88b2/',
            // You can set any number of default request options.
        	'timeout'  => 4000.0,
        	// To work with NLX we need a couple of default headers
        	'headers' => [
        		//'Accept'        => 'application/ld+json',
        		//'Content-Type'  => 'application/json',
        		'Authorization'  => '45c1a4b6-59d3-4a6e-86bf-88a872f35845',
        		//'X-NLX-Request-User-Id' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'				// the id of the user performing the request
        		//'X-NLX-Request-Application-Id' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn' 		// the id of the application performing the request
        		//'X-NLX-Request-Subject-Identifier' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn' 	// an subject identifier for purpose registration (doelbinding)
        		//'X-NLX-Request-Process-Id' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn' 			// a process id for purpose registration (doelbinding)
        		//'X-NLX-Request-Data-Elements' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn' 		// a list of requested data elements
        		//'X-NLX-Request-Data-Subject' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn' 		// a key-value list of data subjects related to this request. e.g. bsn=12345678,kenteken=ab-12-fg
        	],
        ]);
    }

    public function getOnSlug($slug, $force = false)
    {
        $item = $this->cash->getItem('sjabloon_'.$slug);

        if ($item->isHit() && !$force) {
            //return $item->get();
        }

        $response = $this->client->request('GET', $slug);

        $response = json_decode($response->getBody()->getContents(), true);
        $response = $response['template'];

        $item->set($response);
        $item->expiresAt(new \DateTime('tomorrow'));
        $this->cash->save($item);

        return $response;
    }
}
