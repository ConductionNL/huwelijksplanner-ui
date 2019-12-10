<?php
// src/Service/AmbtenaarService.php
namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use GuzzleHttp\Client ;

use App\Service\CommonGroundService;


class AmbtenaarService
{
	private $params;
	private $client;
	private $commonGroundService;
	
	public function __construct(ParameterBagInterface $params, CommonGroundService $commonGroundService)
	{
		$this->params = $params;
		$this->commonGroundService = $commonGroundService;
		
		$this->client= new Client([
				// Base URI is used with relative requests
				'base_uri' => 'http://pdc.zaakonline.nl/',
				// You can set any number of default request options.
				'timeout'  => 4000.0,
		]);
	}
		
	public function getAll()
	{
		$response = $this->client->request('GET','groups/7f4ff7ae-ed1b-45c9-9a73-3ed06a36b9cc');
		$response = json_decode($response->getBody(), true);
		$responses = $response["_embedded"]["products"];
		
		// Lets get the persons for ambtenaren
		//foreach($responses as $key=>$value){
		//	$responses[$key]["person"] = $this->commonGroundService->getSingle($value["persoon"]);			
		//}
		return $responses;
	}
	
}
