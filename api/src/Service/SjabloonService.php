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
				'base_uri' => 'http://resources.demo.zaakonline.nl/',
				// You can set any number of default request options.
				'timeout'  => 4000.0,
		]);
	}	
	
	public function getAll()
	{
		$response = $this->client->request('GET');
		$response = json_decode($response->getBody()->getContents(), true);
		return $response["hydra:member"];
	}
	
	public function getPaginas()
	{
		$response =  $this->client->request('GET', '/sjablonen', [
				'query' => ['type' => 'pagina']
		]);
		
		$response = json_decode($response->getBody()->getContents(), true);
		
		return $response["hydra:member"];
	}
	
	public function getBerichten()
	{
		$response =  $this->client->request('GET', '/sjablonen', [
				'query' => ['type' => 'bericht']
		]);
		
		$response = json_decode($response->getBody()->getContents(), true);
		return $response["hydra:member"];
	}
	
	public function getSlug($slug)
	{
		
		$response =  $this->client->request('GET', '/sjablonen', [
				'query' => ['slug' => $slug]
		]);
		
		$response = json_decode($response->getBody()->getContents(), true);
		return $response["hydra:member"];
	}
	
	public function getOne($id)
	{
		$response = $this->client->request('GET','/sjablonen/'.$id);
		$response = json_decode($response->getBody()->getContents(), true);
		return $response;
	}
	
	public function render($id, $variabelen)
	{
		$response = $this->client->post('/sjablonen/'.$id.'/render', [
				\GuzzleHttp\RequestOptions::JSON => $variabelen
		]);
		$response = json_decode($response->getBody()->getContents(), true);
		return $response;
	}
	
	
	public function save($sjabloon)
	{
		unset($sjabloon['bericht']);
		unset($sjabloon['pagina']);
		
		if($sjabloon['id']){
			$response = $this->client->put('sjablonen/'.$sjabloon['id'], [
					\GuzzleHttp\RequestOptions::JSON => $sjabloon
			]);
		}
		else{
			unset($sjabloon['id']);
			$response = $this->client->post('sjablonen', [
					\GuzzleHttp\RequestOptions::JSON => $sjabloon
			]);
		}
		
		//$response=  $this->client->send($request);
		$response = json_decode($response->getBody()->getContents(), true);
		return $response;
	}
	
}
