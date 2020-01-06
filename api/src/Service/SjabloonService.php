<?php
// src/Service/HuwelijkService.php
namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use GuzzleHttp\Client ;
use GuzzleHttp\RequestOptions;

class SjabloonService
{
	private $params;
	private $client;
	private $session;
	
	public function __construct(ParameterBagInterface $params, SessionInterface $session)
	{
		$this->params = $params;
		$this->session = $session;
		
		$this->client= new Client([
				// Base URI is used with relative requests
				'base_uri' => 'http://wrc.zaakonline.nl/applications/536bfb73-63a5-4719-b535-d835607b88b2/',
				// You can set any number of default request options.
				'timeout'  => 4000.0,
		]);
	}	
	
	public function getOnSlug($slug)
	{
		
		$response =  $this->client->request('GET', $slug);
		
		$response = json_decode($response->getBody()->getContents(), true);
		return $response["template"];
	}
	
}
