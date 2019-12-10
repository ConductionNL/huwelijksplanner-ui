<?php
// src/Service/HuwelijkService.php
namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use GuzzleHttp\Client ;
use GuzzleHttp\RequestOptions;

use App\Service\CommonGroundService;

class HuwelijkService
{
	private $params;
	private $client;
	private $session;
	private $commonGroundService;
	
	public function __construct(ParameterBagInterface $params, SessionInterface $session, CommonGroundService $commonGroundService)
	{
		$this->params = $params;
		$this->session = $session;
		$this->commonGroundService = $commonGroundService;
		
		$this->client= new Client([
				// Base URI is used with relative requests
				'base_uri' => 'http://trouwen.demo.zaakonline.nl/huwelijken',
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
		$response = $this->client->request('GET','/huwelijk/'.$id);
		$response = json_decode($response->getBody(), true);
		//$response = $this->bewitchHuwelijk($response);
		return $response;
	}
		
	public function delete($huwelijk)
	{
		$response = $this->client->request('DELETE','/huwelijk/'.$huwelijk['id']);
		$response = json_decode($response->getBody(), true);
		return $response;
	}
	
	public function getHuwelijkOnPersoon($persoon)
	{
		$response = $this->client->request('GET','/huwelijken',['query' => ['partners' => 'http://brp.demo.zaakonline.nl'.$persoon['burgerservicenummer']]]); //
		$response = json_decode($response->getBody(), true);
		
		// Geen huwelijk, dus laten we er eentje aanmaken
		if($response["hydra:totalItems"]==0){
			$response =  $this->client->post('/huwelijken', [
					RequestOptions::JSON => ['bronOrganisatie' => 123456789, 'partners' => ['http://brp.demo.zaakonline.nl'.$persoon['burgerservicenummer']]]
			]);
			$response = json_decode($response->getBody(), true);
		}
		else{
			$response = $response["hydra:member"][0];			
		}
		
		//s$response = $this->bewitchHuwelijk($response);
		
		return $response;
	}
	
	
	/* Gets the contextual data for a huwelijk */
	public function bewitchHuwelijk($huwelijk)
	{			
		if($huwelijk['locatie'])
		{
			$url = $huwelijk['locatie'];
			$huwelijk['locatie']=$this->commonGroundService->getSingle($url);
			$huwelijk['locatie']['url']=$url;
		}
		if($huwelijk['ambtenaar'])
		{
			$url = $huwelijk['ambtenaar'];
			$huwelijk['ambtenaar']=$this->commonGroundService->getSingle($url);
			$huwelijk['ambtenaar']['url']=$url;
		}
		if($huwelijk['ceremonie'])
		{
			$url = $huwelijk['ceremonie'];
			$huwelijk['ceremonie']=$this->commonGroundService->getSingle($url);
			$huwelijk['ceremonie']['url']=$url;
		}
		
		return $huwelijk;
	}
	
	/* updates the huwelijk to the session */
	public function updateHuwelijk($huwelijk)
	{				
		//$this->session->set('huwelijk', $huwelijk);
		$huwelijk = $this->synchronizeHuwelijk($huwelijk);
		
		return $huwelijk;
	}
	
	/* synchronizes the Huwelijk in session with the api */
	/* @todo this could be sped up using async */
	public function synchronizeHuwelijk($huwelijk)
	{	
		//$huwelijk = $this->session->get('huwelijk');
		//unset($huwelijk['locatie']);
		//unset($huwelijk['primairProduct']);
		//unset($huwelijk['trouwAmbtenaar']);
		//if($huwelijk['locatie']){$huwelijk['locatie'] = $huwelijk['locatie']['url'];}
		//if($huwelijk['ambtenaar']){$huwelijk['ambtenaar'] = $huwelijk['ambtenaar']['url'];}
		//if($huwelijk['ceremonie']){$huwelijk['ceremonie'] = $huwelijk['ceremonie']['url'];}
		//unset();
		//unset($huwelijk['partners']);
		//unset($huwelijk['getuigen']); 
		//unset($huwelijk['ambtenaren']);
		//unset($huwelijk['documenten']);		
		if(isset($huwelijk['getuigen']) && !$huwelijk['getuigen']){
			$huwelijk['getuigen']=[];
		}
		if(isset($huwelijk['extras']) && !$huwelijk['extras']){
			$huwelijk['extras']=[];
		}
		
		if(!$huwelijk['emailadres']){
			unset($huwelijk['emailadres']);
		}
		if(!$huwelijk['telefoonnummer']){
			unset($huwelijk['telefoonnummer']);
		}
		if(!$huwelijk['voornamen']){
			unset($huwelijk['voornamen']);
		}
		if(!$huwelijk['geslachtsnaam']){
			unset($huwelijk['geslachtsnaam']);
		}
		if(!$huwelijk['partner']){
			unset($huwelijk['partner']);
		}
		/*
		if(!$huwelijk['removeId']){
			unset($huwelijk['removeId']);
		}
		*/
		if(!$huwelijk['document']){
			unset($huwelijk['document']);
		}
		if(!$huwelijk['documentType']){
			unset($huwelijk['documentType']);
		}
		if(!$huwelijk['setLocation']){
			unset($huwelijk['setLocation']);
		}
		if(!$huwelijk['setProduct']){
			unset($huwelijk['setProduct']);
		}
		if(!$huwelijk['setOfficial']){
			unset($huwelijk['setOfficial']);
		}
		if(!$huwelijk['bsn']){
			unset($huwelijk['bsn']);
		}
		if(!$huwelijk['datum']){
			unset($huwelijk['datum']);
		}
		if(!$huwelijk['tijd']){
			unset($huwelijk['tijd']);
		}
		if(!$huwelijk['mogelijkeDatums']){
			unset($huwelijk['mogelijkeDatums']);
		}
		if(!$huwelijk['mogelijkeTijden']){
			unset($huwelijk['mogelijkeTijden']);
		}
		if(!$huwelijk['identificatie']){
			unset($huwelijk['identificatie']);
		}
		unset($huwelijk['registratiedatum']);
		unset($huwelijk['wijzigingsdatum']);
		unset($huwelijk['eigenaar']);
		unset($huwelijk['taal']);
		
		$response =  $this->client->put('/huwelijk/'.$huwelijk['id'], [
				RequestOptions::JSON => $huwelijk
		]);
		
		// We gaan het huwelijk nu opnieuw ophalen als object en in de sessie wegschrijven
		$response = $this->client->request('GET','/huwelijk/'.$huwelijk['id']);
		$huwelijk= json_decode($response->getBody(), true);
		
		//$huwelijk = json_decode($response->getBody(),true);
		$this->session->set('huwelijk', $huwelijk);
		
		return $huwelijk;
	}
	
	public function setDate($date, $time)
	{
		$huwelijk = $this->session->get('huwelijk');
		
		$post=[];
		$post['datum'] = $date;
		$post['tijd'] = $time;
		
		$response =  $this->client->put('/huwelijk/'.$huwelijk['id'], [
				RequestOptions::JSON => $post
		]);		
		
		$huwelijk = json_decode($response->getBody(),true);
		
		$this->session->set('huwelijk', $huwelijk);
		
		return $huwelijk;
	}
	public function setLocation($locatie)
	{		
		$huwelijk = $this->session->get('huwelijk');
		$huwelijk['locatie'] = $locatie;
		$huwelijk = $this->synchronizeHuwelijk();
		$this->session->set('huwelijk', $huwelijk);
		
		
		return $huwelijk;
	} 	
	
	public function removeLocation()
	{
		$huwelijk = $this->session->get('huwelijk');
		$huwelijk['locatie'] = null;
		$huwelijk = $this->synchronizeHuwelijk();
		
		$this->session->set('huwelijk', $huwelijk);
		
		return $huwelijk;
	} 
	
	public function setProduct($product)
	{
		$huwelijk = $this->session->get('huwelijk');
		$huwelijk['ceremonie'] = null;
		$huwelijk = $this->synchronizeHuwelijk();
		
		$this->session->set('huwelijk', $huwelijk);
		
		return $huwelijk;
	}
	
	public function removeProduct()
	{
		$huwelijk = $this->session->get('huwelijk');
		$huwelijk['ceremonie'] = null;
		$huwelijk = $this->synchronizeHuwelijk();
		
		$this->session->set('huwelijk', $huwelijk);
		
		return $huwelijk;
	} 
	
	public function setOfficial($id)
	{		
		$huwelijk = $this->session->get('huwelijk');
		
		$response =  $this->client->post('/huwelijk/'.$huwelijk['id'].'/requestOfficial', [
				RequestOptions::JSON => ['setOfficial' => $id]
		]);
		
		
		$huwelijk = json_decode($response->getBody(),true);
		
		$this->session->set('huwelijk', $huwelijk);
		
		return $huwelijk;
	}
	
	public function removeOfficial()
	{
		$huwelijk = $this->session->get('huwelijk');
		
		$response =  $this->client->put('/huwelijk/'.$huwelijk['id'], [
				RequestOptions::JSON => ['trouwAmbtenaar' => null]
		]);
		
		$huwelijk = json_decode($response->getBody(),true);
		
		$this->session->set('huwelijk', $huwelijk);
		
		return $huwelijk;
	}
	
	
	public function melding()
	{
		$huwelijk = $this->session->get('huwelijk');
		
		$response =  $this->client->post('/huwelijk/'.$huwelijk['id'].'/melding', [
				RequestOptions::JSON => []
		]);
		
		
		$huwelijk = json_decode($response->getBody(),true);
		
		$this->session->set('huwelijk', $huwelijk);
		
		return $huwelijk;
	}
	
		
	public function aanvraag()
	{
		$huwelijk = $this->session->get('huwelijk');
		
		$response =  $this->client->post('/huwelijk/'.$huwelijk['id'].'/aanvraag', [
				RequestOptions::JSON => []
		]);
		
		
		$huwelijk = json_decode($response->getBody(),true);
		
		$this->session->set('huwelijk', $huwelijk);
		
		return $huwelijk;
	}
		
	public function invitePartner($partner)
	{		
		$huwelijk = $this->session->get('huwelijk');
				
		$response =  $this->client->post('/huwelijk/'.$huwelijk['id'].'/addPartner', [
				RequestOptions::JSON => $partner
		]);
		
		
		$huwelijk = json_decode($response->getBody(),true);
		
		$this->session->set('huwelijk', $huwelijk);
		
		return $huwelijk;
	}
	
	
	
	public function save($huwelijk)
	{
		if($huwelijk['id']){
			$response = $this->client->put('huwelijk/'.$huwelijk['id'], [
					\GuzzleHttp\RequestOptions::JSON => $huwelijk
			]);
		}	
		else{
			$huwelijk= $this->client->post('huwelijken', [
					\GuzzleHttp\RequestOptions::JSON => $huwelijk
			]);
		}
		//$response=  $this->client->send($request);
		$response = json_decode($response->getBody(), true);
		return $response;
	}
}
