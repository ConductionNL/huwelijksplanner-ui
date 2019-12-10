<?php
// src/Service/HuwelijkService.php
namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use GuzzleHttp\Client ;
use GuzzleHttp\RequestOptions;

class ResourceService
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
				'base_uri' => 'http://resources.zaakonline.nl/producten',
				// You can set any number of default request options.
				'timeout'  => 4000.0,
		]);
	}
	
	public function getAll()
	{
		$response = $this->client->request('GET');
		$response = json_decode($response->getBody(), true);
		return $response["hydra:member"];
	}
	
	public function getOne($id)
	{
		$response = $this->client->request('GET','/producten/'.$id);
		$response = json_decode($response->getBody(), true);
		return $response;
	}
	
	public function save($locatie)
	{
		if($locatie['id']){
			$response = $this->client->put('locaties/'.$locatie['id'], [
					\GuzzleHttp\RequestOptions::JSON => $locatie
			]);
		}
		else{
			$locatie= $this->client->post('locaties', [
					\GuzzleHttp\RequestOptions::JSON => $locatie
			]);
		}
		//$response=  $this->client->send($request);
		$response = json_decode($response->getBody(), true);
		return $response;
	}
	
}
