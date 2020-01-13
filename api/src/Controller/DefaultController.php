<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


use App\Service\PdcService;
use App\Service\SjabloonService;
use App\Service\BRPService;
use App\Service\RequestService;
use App\Service\RequestTypeService;
use App\Service\ContactService;
use App\Service\AssentService;

use App\Service\CommonGroundService;

/**
 */
class DefaultController extends AbstractController
{
	/**
	 * @Route("/")
	 */
	public function indexAction(Session $session, SjabloonService $sjabloonService)
	{
		$requestType = $session->get('requestType');
		$request = $session->get('request');
		$user = $session->get('user');
		$products = [];
		
		
		$variables = ["requestType"=>$requestType,"request"=>$request,"user"=>$user,"products"=>$products];
		
		if($template = $sjabloonService->getOnSlug('trouwen')){
			// We want to include the html in our own template
			$html = $template['content'];
			
			$template = $this->get('twig')->createTemplate($html);
			$template = $template->render($variables);
			
			return $response = new Response(
					$template,
					Response::HTTP_OK,
					['content-type' => 'text/html']
					);
		}
		else{
			throw $this->createNotFoundException('This page could not be found');
		}	
	}
	
	/**
	 * @Route("request/new")
	 */
	public function newrequestAction(Session $session, RequestService $requestService, RequestTypeService $requestTypeService, ContactService $contactService, AssentService $assentService, CommonGroundService $commonGroundService)
	{
		
		$user = $session->get('user');
		
		// Lets also set the request type
		$requestType = $requestTypeService->getRequestType('5b10c1d6-7121-4be2-b479-7523f1b625f1');
		
		// Okey we don't have ay requests so lets start a marige request
		$request= [];
		$request['request_type']='http://vtc.zaakonline.nl/request_types/'.$requestType['id'];
		$request['target_organization']='002220647';
		$request['submitter']=$user['burgerservicenummer'];
		$request['status']='incomplete';
		$request['properties']= [];
		
		$request = $requestService->createRequest($request);		
		
		$requestType = $requestService->checkRequestType($request, $requestType);
		$session->set('requestType', $requestType);
		
		$contact = [];
		$contact['givenName']= $user['naam']['voornamen'];
		$contact['familyName']= $user['naam']['geslachtsnaam'];
		$contact= $contactService->createContact($contact);
		
		$assent = [];
		$assent['name'] = 'Instemming huwelijk partnerschp';
		$assent['description'] = 'U bent automatisch toegevoegd aan een  huwelijk/partnerschap omdat u deze zelf heeft aangevraagd';
		$assent['contact'] = 'http://cc.zaakonline.nl'.$contact['_links']['self']['href'];
		$assent['requester'] = $requestType['source_organization']; 
		$assent['person'] = $user['burgerservicenummer'];
		$assent['request'] = $request['id'];
		$assent['status'] = 'granted';
		
		$order = [];
		$order['name'] = 'Huwelijk of Partnerschap';
		$order['description'] = 'Huwelijk of Partnerschap';
		$order = $commonGroundService->createResource($order, "https://orc.zaakonline.nl/orders");
		$request['properties']['order'] = 'https://orc.zaakonline.nl'.$order['_links']['self']['href'];
		
		$assent = $assentService->createAssent($assent);
		if(!array_key_exists('partners',$request['properties'])){
			$request['properties']['partners'] = [];
		}
		$request['properties']['partners'][] = 'http://irc.zaakonline.nl'.$assent['_links']['self']['href'];
		
		$request = $requestService->updateRequest($request);
		
		$session->set('request', $request);
		
		// If we have a starting position lets start there
		if(array_key_exists ("current_stage", $request) && $request["current_stage"] != null){
			$start = $request["current_stage"];
		}
		elseif(count($requestType['stages']) >0){
			$start = $requestType['stages'][0]["slug"];
		}
		else{
			$start = "ceremonies";
		}
		
		return $this->redirect($this->generateUrl('app_default_slug',["slug"=>$start]));
	}
	
	
	/**
	 * @Route("request/submit")
	 */
	public function submitrequestAction(Session $session, RequestService $requestService)
	{		
		$request = $session->get('request');
		$request['status'] = 'submited';
		
		if($request = $requestService->updateRequest($request)){
			$session->set('request', $request);
			
			$this->addFlash('Uw verzoek is ingediend');
		}
		else{
			$this->addFlash('Uw verzoek kon niet worden ingediend');
		}
		
		return $this->redirect($this->generateUrl('app_default_view',["slug"=>"checklist"]));
	}
	
	/**
	 * @Route("request/cancel")
	 */
	public function cancelrequestAction(Session $session, RequestService $requestService)
	{		
		$request = $session->get('request');
		$request['status'] = 'cancelled';
		
		if($request = $requestService->updateRequest($request)){
			$session->set('request', $request);						
			$this->addFlash('Uw verzoek is geanuleerd');
		}
		else{
			$this->addFlash('Uw verzoek kon niet worden geanuleerd');
		}
		
		return $this->redirect($this->generateUrl('app_default_view',["slug"=>"checklist"]));
	}
	
	/**
	 * @Route("request/{id}")
	 */
	public function loadrequestAction(Session $session, RequestService $requestService, RequestTypeService $requestTypeService , $id)
	{
		
		$request = $requestService->getRequestOnId($id);
		$session->set('request', $request);
		
		// Lets also set the request type
		$requestType = $requestTypeService->getRequestType($request['request_type']);		
		$requestType = $requestService->checkRequestType($request, $requestType);
		
		$session->set('requestType', $requestType);
		
		
		// If we have a starting position lets start there
		if(array_key_exists ("current_stage", $request) && $request["current_stage"] != null){
			$start = $request["current_stage"];
		}
		elseif(count($requestType['stages']) >0){
			$start = $requestType['stages'][0]["slug"];
		}
		else{			
			$start = "ceremonies";
		}
		
		return $this->redirect($this->generateUrl('app_default_slug',["slug"=>$start]));
	}
	
	/**
	 * @Route("/login")
	 */
	public function loginAction(Session $session, Request $request, BRPService $brpService, RequestService $requestService, RequestTypeService $requestTypeService, ContactService $contactService, AssentService $assentService)
	{
		$start = "ceremonies";
		
		$bsn = $request->request->get('bsn');
		if(!$bsn){
			$bsn =  $request->query->get('bsn');
		}
		if(!$bsn){
			// No login suplied so redirect to digispoof
			return $this->redirect('http://digispoof.zaakonline.nl?responceUrl='.urlencode($httpRequest->getScheme() . '://' . $httpRequest->getHttpHost().$httpRequest->getBasePath()));
		}
		
		if($bsn && $persoon = $brpService->getPersonOnBsn($bsn)){
			$session->set('user', $persoon);
			
			
			if($requests = $requestService->getRequestOnSubmitter($persoon['burgerservicenummer'])){
				return $this->redirect($this->generateUrl('app_default_slug',["slug"=>"requests"]));;
			}
			else{
				// Okey we don't have ay requests so lets start a marige request
				
				// Lets also set the request type
				$requestType = $requestTypeService->getRequestType('5b10c1d6-7121-4be2-b479-7523f1b625f1');
				
				$request= [];
				$request['requestType']='http://vtc.zaakonline.nl/request_types/'.$requestType['id'];
				$request['targetOrganization']='002220647';
				$request['submitter']=$persoon['burgerservicenummer'];
				$request['status']='incomplete';
				$request['properties']= [];
				$request['properties']['partner1']= $persoon['burgerservicenummer'];
				
				$request = $requestService->createRequest($request);
				$session->set('requestType', $requestType);
				
				$requestType = $requestService->checkRequestType($request, $requestType);
				
				$requestType = $requestService->checkRequestType($request, $requestType);
				
				$contact = [];
				$contact['givenName']= $persoon['naam']['voornamen'];
				$contact['familyName']= $persoon['naam']['geslachtsnaam'];
				$contact= $contactService->createContact($contact);
				
				$assent = [];
				$assent['name'] = 'Instemming huwelijk partnerschp';
				$assent['description'] = 'U bent automatisch toegevoegd aan een  huwelijk/partnerschap omdat u deze zelf heeft aangevraagd';
				$assent['contact'] = 'http://cc.zaakonline.nl'.$contact['_links']['self']['href'];
				$assent['requester'] = $requestType['source_organization']; 
				$assent['person'] = $persoon['burgerservicenummer'];
				$assent['request'] = $request['id'];
				$assent['status'] = 'granted';
				$assent['requester'] = $persoon['burgerservicenummer'];
				
				$assent= $assentService->createAssent($assent);
				if(!array_key_exists('partners',$request['properties'])){
					$request['properties']['partners'] = [];
				}
				$request['properties']['partners'][] = 'http://irc.zaakonline.nl'.$assent['_links']['self']['href'];
				
				$request = $requestService->updateRequest($request);
				
				$session->set('request', $request);				
				
				
				// If we have a starting position lets start there
				if(count($requestType['stages']) >0){
					$start = $requestType['stages'][0]["slug"];
				}
			}
			$this->addFlash('success', 'Welkom '.$persoon['naam']['voornamen']);
		}
		else{
			$this->addFlash('danger', 'U kon helaas niet worden ingelogd');
		}
		
		return $this->redirect($this->generateUrl('app_default_slug',["slug"=>$start]));
	}
	
	/**
	 * @Route("/logout")
	 */
	public function logoutAction(Session $session)
	{
		$session->set('requestType',false);
		$session->set('request',false);
		$session->set('user',false);
		$session->set('employee',false);
		$session->set('contact',false);
				
		$this->addFlash('success', 'U bent uitgelogd');
		
		return $this->redirect($this->generateUrl('app_default_slug',["slug"=>"trouwen"]));
	}
	
	/**
	 * @Route("/assent/add/{property}")
	 */
	public function assentAddAction(Session $session, Request $httpRequest, $property, RequestService $requestService, ContactService $contactService, AssentService $assentService)
	{
		// First we need to make an new assent
		$assent = [];
		$assent['name'] = 'Instemming huwelijk partnerschp';
		$assent['description'] = 'U bent automatisch toegevoegd aan een  huwelijk/partnerschap omdat u deze zelf heeft aangevraagd';
		$assent['requester'] = $requestType['source_organization'];
		$assent['person'] = $persoon['burgerservicenummer'];
		$assent['request'] = $request['id'];
		$assent['status'] = 'granted';
		$assent['requester'] = $persoon['burgerservicenummer'];
		
		$assent= $assentService->createAssent($assent);
		if(!array_key_exists($property ,$request['properties'])){
			$request['properties'][$property] = [];
		}
		$request['properties'][$property][] = 'http://irc.zaakonline.nl'.$assent['_links']['self']['href'];
		
		$request = $requestService->updateRequest($request);
		
		$session->set('requestType',false);
		$session->set('request',false);		
		
		return $this->redirect($this->generateUrl('app_default_assentLogin',["id"=>$assent["id"]]));
	}
	
	/**
	 * @Route("/assent/{id}")
	 */
	public function assentLoginAction(Session $session, Request $httpRequest, $id, RequestService $requestService, CommonGroundService $commongroundService, BRPService $brpService, AssentService $assentService)
	{
		// Lets first see if we have a login
		$bsn = $request->request->get('bsn');
		if(!$bsn){
			$bsn =  $request->query->get('bsn');
		}
		if(!$bsn){ 
			// No login suplied so redirect to digispoof
			return $this->redirect('http://digispoof.zaakonline.nl?responceUrl='.urlencode($httpRequest->getScheme() . '://' . $httpRequest->getHttpHost().$httpRequest->getBasePath()));
		}
			
		if($bsn && $persoon = $brpService->getPersonOnBsn($bsn)){
			
			$session->set('user', $persoon);
			$assent = $assentService->getAssent($id);
			$request = $commongroundService->getResource($assent['request']);
			$session->set('request', $request);
			
			// Lets also set the request type
			$requestType = $requestTypeService->getRequestType($request['requestType']);
			$requestType = $requestService->checkRequestType($request, $requestType);
			
			$session->set('requestType', $requestType);
			
			$this->addFlash('success', 'Welkom '.$persoon['naam']['voornamen']);
			
			
			$products = [];
			$variables = ["requestType"=>$requestType,"request"=>$request,"user"=>$user,"products"=>$products,"assent"=>$assent];
			
			$template = $sjabloonService->getOnSlug('assent');
			
			// We want to include the html in our own template
			$html = $template['content'];
			
			$template = $this->get('twig')->createTemplate($html);
			$template = $template->render($variables);
			
			return $response = new Response(
				$template,
				Response::HTTP_OK,
				['content-type' => 'text/html']
			);
				
		}
		else{
			$this->addFlash('danger', 'U kon helaas niet worden ingelogd');
		}
		
		// If nothing sticks we redirect the user to the home page
		return $this->redirect($this->generateUrl('app_default_index'));
	}
		
	/**
	 * @Route("/data")
	 */
	public function dataAction(Session $session)
	{
		$request = $session->get('request');
		$user = $session->get('user');
		
		$response = new JsonResponse($request);
		return $response;
	}	
	
	/**
	 * @Route("/{slug}")
	 */
	public function slugAction(Session $session, SjabloonService $sjabloonService, PdcService $pdcService, RequestService $requestService, $slug)
	{
		$requestType = $session->get('requestType');
		$request = $session->get('request');
		$user = $session->get('user');
		$products = [];
		$variables = ["requestType"=>$requestType,"request"=>$request,"user"=>$user,"products"=>$products];
		
		// var_dump($request);
		
		switch ($slug) {
			case null :
				$slug = 'trouwen';
				break;
			case 'ambtenaren':
				$variables['products']  = $pdcService->getProducts(['groups.id'=>'7f4ff7ae-ed1b-45c9-9a73-3ed06a36b9cc']);
				break;
			case 'locaties':
				$variables['products'] = $pdcService->getProducts(['groups.id'=>'170788e7-b238-4c28-8efc-97bdada02c2e']);
				break;
			case 'plechtigheid': 
				$variables['products'] = $pdcService->getProducts(['groups.id'=>'1cad775c-c2d0-48af-858f-a12029af24b3']);
				break;
			case 'extras':
				$variables['products'] = $pdcService->getProducts(['groups.id'=>'f8298a12-91eb-46d0-b8a9-e7095f81be6f']);
				break; 
			case 'requests':
				$variables['requests'] = $requestService->getRequestOnSubmitter($user['burgerservicenummer']);
				break; 
		}
		
		
		if($template = $sjabloonService->getOnSlug($slug)){
			// We want to include the html in our own template
			$html = $template['content'];
			
			$template = $this->get('twig')->createTemplate($html);
			$template = $template->render($variables);
			
			return $response = new Response(
				$template,
				Response::HTTP_OK,
				['content-type' => 'text/html']
			);
		}
		else{			
			throw $this->createNotFoundException('This page could not be found');
		}	
	}
	
	/**
	 * @Route("/{slug}/assent/{role}")
	 */
	public function assentAction(Session $session, Request $httpRequest, $slug, $role, RequestService $requestService, ContactService $contactService, AssentService $assentService)
	{
		
		$requestType = $session->get('requestType');
		$request = $session->get('request');
		$user = $session->get('user');
		
		
		if (!$httpRequest->isMethod('POST')) {
			return false;
		}
		
		// First we need to create an assent	
		$contact = [];
		$contact['givenName']= $httpRequest->request->get('givenName');;
		$contact['familyName']= $httpRequest->request->get('familyName');;
		$contact['emails']=[];
		$contact['emails'][]=["name"=>"primary","email"=> $httpRequest->request->get('email')];
		$contact['telephones']=[];
		$contact['telephones'][]=["name"=>"primary","telephone"=> $httpRequest->request->get('telephone')];
		$contact= $contactService->createContact($contact);
		
		/* @todo onderstaande gaat een fout gooien als getuigen worden uitgenodigd voordat het huwelijkstype isgeselecteer (ja dat kan) */
		$assent = [];
		$assent['name'] = 'Instemming als '.$role.' bij '.$request["properties"]["type"];
		$assent['description'] = 'U bent uitgenodigd als '.$role.' voor het '.$request["properties"]["type"].' van A en B';
		$assent['contact'] = 'http://cc.zaakonline.nl'.$contact['_links']['self']['href'];
		$assent['requester'] = $requestType['source_organization']; 
		$assent['request'] = $request['id'];
		$assent['status'] = 'requested';
		$assent['requester'] = $user['burgerservicenummer'];
		
		$assent= $assentService->createAssent($assent);
		
		// Lets get the curent property
		$arrIt = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($requestType['stages']));
		
		foreach ($arrIt as $sub) {
			$subArray = $arrIt->getSubIterator();
			if ($subArray['slug'] === $slug) {
				$property = iterator_to_array($subArray);
				break;
			}
		}
		
		// Lets see if an array already exisits for this property
		if(!array_key_exists($property["name"], $request['properties'])){
			$request['properties'][$property["name"]] = [];
		}		
		
		$request['properties'][$property["name"]][] = 'http://irc.zaakonline.nl'.$assent['_links']['self']['href'];
		
		if($request = $requestService->updateRequest($request)){
			$request["current_stage"] = $property["next"];
			$request = $requestService->updateRequest($request);
			$session->set('request', $request);
			
			$requestType = $requestService->checkRequestType($request, $requestType);
			$session->set('requestType', $requestType);
			
			
			$this->addFlash('success', ucfirst($slug).' is ingesteld');
			$slug = $property["next"];
			return $this->redirect($this->generateUrl('app_default_slug',["slug"=>$slug]));
		}
		else{
			$this->addFlash('danger', ucfirst($slug).' kon niet worden ingesteld');
			return $this->redirect($this->generateUrl('app_default_view',["slug"=>$slug,"id"=>$id]));
		}
	}
	
	/**
	 * @Route("/{slug}/add/{id}", requirements={"id"=".+"})
	 */
	public function addAction(Session $session, $slug, $id, RequestService $requestService)
	{
		
		$requestType = $session->get('requestType');
		$request = $session->get('request');
		$user = $session->get('user');
		
		// Lets get the curent property
		$arrIt = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($requestType['stages']));
		
		foreach ($arrIt as $sub) {
			$subArray = $arrIt->getSubIterator();
			if ($subArray['slug'] === $slug) {
				$property = iterator_to_array($subArray);
				break;
			}
		}
		
		// Lets see if an array already exisits for this property
		if(!array_key_exists($property["name"], $request['properties'])){
			$request['properties'][$property["name"]] = [];
		}
			
				
		$request['properties'][$property["name"]][] = $id;
		
		if($request = $requestService->updateRequest($request)){
			$request["current_stage"] = $property["next"];
			$request = $requestService->updateRequest($request);
			$session->set('request', $request);						
			
			$requestType = $requestService->checkRequestType($request, $requestType);
			$session->set('requestType', $requestType);			
						
			
			$this->addFlash('success', ucfirst($slug).' is ingesteld');
			$slug = $property["next"];
			return $this->redirect($this->generateUrl('app_default_slug',["slug"=>$slug]));
		}
		else{
			$this->addFlash('danger', ucfirst($slug).' kon niet worden ingesteld');
			return $this->redirect($this->generateUrl('app_default_view',["slug"=>$slug,"id"=>$id]));
		}
	}
	
	/**
	 * @Route("/{slug}/set/{id}" , requirements={"id"=".+"})
	 */
	public function setAction(Session $session, $slug, $id, RequestService $requestService)
	{
		$requestType = $session->get('requestType');
		$request = $session->get('request');
		$user = $session->get('user');
		
		// Lets get the curent property
		$arrIt = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($requestType['stages']));
		
		foreach ($arrIt as $sub) {
			$subArray = $arrIt->getSubIterator();
			if ($subArray['slug'] === $slug) {
				$property = iterator_to_array($subArray);
				break;
			}
		}
		
		$request['properties'][$property["name"]] = $id;
		
		// hardcode overwrite for "gratis trouwen"
		if(array_key_exists("plechtigheid", $request['properties']) && $request['properties']["plechtigheid"] == "https://pdc.zaakonline.nl/products/0cd41e70-2a20-4e82-a3ec-22ee9451b3b8"){
			$request['properties']['locatie']="https://pdc.zaakonline.nl/products/5a0ad366-9f10-4002-adcb-bac47143b93b";
			$request['properties']['ambtenaar']="https://pdc.zaakonline.nl/products/9d7c1c5b-3e65-4429-90ec-16e7371f2360";
		}
		
				
		if($request = $requestService->updateRequest($request)){
			$request["current_stage"] = $property["next"];
			$request = $requestService->updateRequest($request);
			$session->set('request', $request);
			
			$requestType = $requestService->checkRequestType($request, $requestType);
			$session->set('requestType', $requestType);	
			
			// Lets find the stage that we are add
			$arrIt = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($requestType['stages']));
			
			
			$this->addFlash('success', ucfirst($slug).' is ingesteld');
			$slug = $property["next"];
			return $this->redirect($this->generateUrl('app_default_slug',["slug"=>$slug]));
		}
		else{			
			$this->addFlash('danger', ucfirst($slug).' kon niet worden ingesteld');
			return $this->redirect($this->generateUrl('app_default_view',["slug"=>$slug,"id"=>$id]));
		}
	}
	
	/**
	 * @Route("/{slug}/datum")
	 */
	public function datumAction(Session $session, $slug, Request $httprequest, RequestService $requestService)
	{
		$requestType = $session->get('requestType');
		$request = $session->get('request');
		$user = $session->get('user');
		
		// Lets get the curent property
		$arrIt = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($requestType['stages']));
		
		foreach ($arrIt as $sub) {
			$subArray = $arrIt->getSubIterator();
			if ($subArray['slug'] === $slug) {
				$property = iterator_to_array($subArray);
				break;
			}
		}
		/* @todo we should turn this into symfony form */
		if ($httprequest->isMethod('POST') && $httprequest->request->get('datum')) {
			
			//var_dump($request->request->get('datum'));
			
			$dateArray = (explode(" ", $httprequest->request->get('datum')));
			$date = strtotime($dateArray[1].' '.$dateArray[2].' '.$dateArray[3]);
			$postdate = date('Y-m-d',$date);
			$displaydate = date('d-m-Y',$date);
			
			
			$request['properties']['datum'] = $displaydate;			
		}
		
		$request['properties'][$property["name"]] = $displaydate;
				
		
		if($request = $requestService->updateRequest($request)){
			$request["current_stage"] = $property["next"];
			$request = $requestService->updateRequest($request);
			$session->set('request', $request);
			
			$requestType = $requestService->checkRequestType($request, $requestType);
			$session->set('requestType', $requestType);
			
			// Lets find the stage that we are add
			$arrIt = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($requestType['stages']));
			
			
			$this->addFlash('success', ucfirst($slug).' is ingesteld');
			$slug = $property["next"];
			return $this->redirect($this->generateUrl('app_default_slug',["slug"=>$slug]));
		}
		else{
			$this->addFlash('danger', ucfirst($slug).' kon niet worden ingesteld');
			return $this->redirect($this->generateUrl('app_default_slug',["slug"=>$slug]));;
		}
	}
	/**
	 * @Route("/{slug}/unset/{id}")
	 */
	public function unsetAction(Session $session, $slug, $id, RequestService $requestService)
	{
		$requestType = $session->get('requestType');
		$request= $session->get('request');
		$user = $session->get('user');
		
		// Lets get the curent property
		$arrIt = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($requestType['stages']));
		
		foreach ($arrIt as $sub) {
			$subArray = $arrIt->getSubIterator();
			if ($subArray['slug'] === $slug) {
				$property = iterator_to_array($subArray);
				break;
			}
		}
		
		if(is_array($request['properties'][$property["name"]])){			
			$key = array_search($id, $request['properties']); 
			unset($request['properties'][$property["name"]][$key]);
		}
		else{
			$request['properties'][$property["name"]] = null;
		}
		
		if($request = $requestService->updateRequest($request)){			
			
			$request["current_stage"] = $property["slug"];
			$request = $requestService->updateRequest($request);
			
			$requestType = $requestService->checkRequestType($request, $requestType);
			$session->set('request', $request);	
			$session->set('requestType', $requestType);	
			
			$this->addFlash('success', ucfirst($slug).' geanuleerd');
			return $this->redirect($this->generateUrl('app_default_slug',["slug"=>$slug]));
		}
		else{
			$this->addFlash('danger', ucfirst($slug).' kon niet worden geanuleerd');
			return $this->redirect($this->generateUrl('app_default_slug',["slug"=>$slug]));
		}
		
	}
	
	/**
	 * @Route("/{slug}/{id}")
	 */
	public function viewAction(Session $session, $slug, $id, SjabloonService $sjabloonService, PdcService $pdcService)
	{
		$requestType = $session->get('requestType');
		$request = $session->get('request');
		$user = $session->get('user');
		$product = $pdcService->getProduct($id);
		
		$variables = ["request"=>$request,"user"=>$user,"product"=>$product,"requestType"=>$requestType,];
		
		if($template = $sjabloonService->getOnSlug($slug)){
			// We want to include the html in our own template
			$html = $template['content'];
			
			$template = $this->get('twig')->createTemplate($html);
			$template = $template->render($variables);
			
			return $response = new Response(
					$template,
					Response::HTTP_OK,
					['content-type' => 'text/html']
					);
		}
		else{
			throw $this->createNotFoundException('This page could not be found');
		}	
	}
}
