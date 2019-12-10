<?php
// src/Service/HuwelijkService.php
namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use GuzzleHttp\Client ;
use GuzzleHttp\RequestOptions;

class CommonGroundService
{
	public function getSingle($url)
	{
		if(!$url){
			return null;
		}
		$client = new Client([
				// You can set any number of default request options.
				'timeout'  => 2.0,
		]);
		
		$response = $client->request('GET', $url);
		$response = json_decode($response->getBody(), true);
		$response['@url']=$url;
		
		return $response;
	}
	
}
