<?php
// src/Service/BRPService.php
namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use GuzzleHttp\Client;
use Symfony\Component\Cache\Adapter\AdapterInterface as CacheInterface;

class RequestService
{
	private $params;
	private $cache;
	private $client;
	
	public function __construct(ParameterBagInterface $params, CacheInterface $cache)
	{
		$this->params = $params;
		$this->cash = $cache;
		
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
	
	public function getRequestOnId($id, $force = false)
	{
		$item = $this->cash->getItem('request_'.$id);
		if ($item->isHit() && !$force) {
			return $item->get();
		}		
		
		$response = $this->client->request('GET','/requests/'.$id, [
			'headers' => [
			'Accept' => 'application/json'
			]
		]
		);
		
		$response = json_decode($response->getBody(), true);		
		
		
		$item->set($response);
		$item->expiresAt(new \DateTime('tomorrow'));
		$this->cash->save($item);
		
		return $response;
	}
	
	public function getRequestOnUri($uri)
	{
		$response = $this->client->request('GET',$uri, [
				'headers' => [
						'Accept' => 'application/json'
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
	
	
	
	public function checkRequestType($request, $requestType)
	{
		
		foreach($requestType["stages"] as $key=>$stage){	
			
			
			// Overwrites for omzetten
			if(
					(
					$stage["name"] == 'getuigen' || 
					$stage["name"] == 'ambtenaar' || 
					$stage["name"] == 'locatie' ||
					$stage["name"] == 'extras' || 
					$stage["name"] == 'plechtigheid' || 
					$stage["name"] == 'melding') 
					&&
					array_key_exists("type", $request['properties'])
					&&
					$request['properties']['type']=="omzetten"
				){
					$requestType["stages"][$key]["completed"] = true;
			}
			if(
					(
							$stage["name"] == 'getuigen' ||
							$stage["name"] == 'ambtenaar' ||
							$stage["name"] == 'locatie' ||
							$stage["name"] == 'extras' ||
							$stage["name"] == 'plechtigheid' ||
							$stage["name"] == 'melding')
					&&
					array_key_exists("type", $request['properties'])
					&&
					$request['properties']['type']!="omzetten"
					){
						$requestType["stages"][$key]["completed"] = false;
			}
			
			
			// Lets see is we have a value for this stage in our request and has a value		
			if(array_key_exists ($stage["name"], $request["properties"]) && $request["properties"][$stage["name"]] != null){
				
				// Let get the validation rules from the request type
				$arrIt = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($requestType['properties']));
				
				foreach ($arrIt as $sub) {
					$subArray = $arrIt->getSubIterator();
										
					if (array_key_exists("name", $subArray) and $subArray['name'] === $stage["name"]) {
						$property = iterator_to_array($subArray);
						break;
					}
				}
				// Als we een waarde hebben en het hoefd geen array te zijn
				if($property["type"] != "array"){
					$requestType["stages"][$key]["completed"] = true;
				}
				// als het een array is zonder minimum waarden
				elseif(!array_key_exists("min_items",$property)){
					$requestType["stages"][$key]["completed"] = true;										
				}
				// als de array een minimum waarde heeft en die waarde wordt gehaald
				elseif(array_key_exists("min_items",$property) && $property["min_items"] && count($request["properties"][$stage["name"]]) >= (int) $property["min_items"]){
					$requestType["stages"][$key]["completed"] = true;					
				}
				else{
					$requestType["stages"][$key]["completed"] = false;	
				}
				
				//var_dump($key);
				//var_dump($property["type"]);
				//var_dump($property["min_items"]);
				//var_dump($request["properties"]);
				//var_dump($requestType["stages"][$key]);
			}		
		}
		//var_dump($requestType["stages"]);
		//die;
		
		return $requestType;
	}
	
}
