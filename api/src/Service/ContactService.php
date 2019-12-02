<?php
// src/Service/BRPService.php
namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use GuzzleHttp\Client;

class ContactService
{
	private $params;
	private $client;
	
	public function __construct(ParameterBagInterface $params)
	{
		$this->params = $params;
		
		$this->client= new Client([
				// Base URI is used with relative requests
				'base_uri' => 'http://206.189.243.106',
				// You can set any number of default request options.
				'timeout'  => 4000.0,
				'body' => 'raw data',
		]);
	}
	
	public function getContact($id)
	{
		$response = $this->client->request('GET','/people/'.$id, [
				'headers' => [
						//'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
				]
		]
				);
		
		$response = json_decode($response->getBody(), true);
		return $response;
	}
	
	public function getContactOnUri($uri)
	{
		$response = $this->client->request('GET',$uri, [
				'headers' => [
						//'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
				]
			]
		);
		
		$response = json_decode($response->getBody(), true);
		return $response;
	}
	
	
	public function createContact($contact)
	{
		$response = $this->client->request('POST','/people', [
				'json' => $contact,
				'headers' => [
						//'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
				]
			]
		);
		
		$response = json_decode($response->getBody(), true);
		return $response;
	}
	
	
	public function updateContact($contact)
	{
	    $response = $this->client->request('PUT','/people/'.$contact['id'], [
	    		'json' => $contact,
				'headers' => [
						//'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
				]
			]
		);
		
		$response = json_decode($response->getBody(), true);
		return $response;
	}
	
}
