<?php
// src/Service/BRPService.php
namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use GuzzleHttp\Client;

class RequestService
{
	private $params;
	private $client;
	
	public function __construct(ParameterBagInterface $params)
	{
		$this->params = $params;
		
		$this->client= new Client([
				// Base URI is used with relative requests
				'base_uri' => 'http://vrc.zaakonline.nl',
				// You can set any number of default request options.
				'timeout'  => 4000.0,
				'body' => 'raw data',
		]);
	}
	
	public function getRequests($query)
	{
	    $response = $this->client->request('GET','/requests', [
	        'headers' => [
	            //'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
	        ]
	    ]
	        );
	    
	    $response = json_decode($response->getBody(), true);
	    return $response['_embedded']['item'];
	}
	
	public function getRequestOnSubmitter($indiener)
	{
		$response = $this->client->request('GET','/requests?submitter='.$indiener, [
				'headers' => [
						//'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
				],
				'query' => [
						'submitter' => $indiener
				]
			]
		);
		
		$response = json_decode($response->getBody(), true);
		
		if($response['totalItems'] > 0){			
			return $response['_embedded']['item'];
		}
			
		// Lets default to false here
		return false;
	}
	
	public function getRequestOnType($indiener, $verzoek = 'http://vtc.zaakonline.nl/')
	{		
		$response = $this->client->request('GET','/requests', [
				'headers' => [
								//'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
				],
				'query' => [
					'submitter' => $indiener,
					'request_type' => $verzoek
				]
			]
		);
		
		$response = json_decode($response->getBody(), true);
		return $response;
	}
	
	public function getRequestOnId($id)
	{
		$response = $this->client->request('GET','/requests/'.$id, [
				'headers' => [
						//'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
				]
		]
				);
		
		$response = json_decode($response->getBody(), true);
		return $response;
	}
	
	public function getRequestOnUri($uri)
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
	
	
	public function createRequest($request)
	{
		$response = $this->client->request('POST','/requests', [
				'json' => $request,
				'headers' => [
						//'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
				]
			]
		);
		
		$response = json_decode($response->getBody(), true);
		return $response;
	}
	
	
	public function updateRequest($request)
	{
		$response = $this->client->request('PUT','/requests/'.$request['id'], [
				'json' => $request,
				'headers' => [
						//'x-api-key' => '64YsjzZkrWWnK8bUflg8fFC1ojqv5lDn'
				]
			]
		);
		
		$response = json_decode($response->getBody(), true);
		return $response;
	}
	
}
