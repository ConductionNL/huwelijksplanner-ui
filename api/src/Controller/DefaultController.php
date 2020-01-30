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
use App\Service\HuwelijkService;

/**
 */
class DefaultController extends AbstractController
{
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
		
		//$request['properties']['order'] = 'https://orc.zaakonline.nl'.$order['_links']['self']['href'];
		
		$assent = $assentService->createAssent($assent);
		if(!array_key_exists('partners',$request['properties'])){
			$request['properties']['partners'] = [];
		}
		$request['properties']['partners'][] = 'http://irc.zaakonline.nl'.$assent['_links']['self']['href'];
		
		
		//$order = [];
		$order['name'] = 'Huwelijk of Partnerschap';
		$order['description'] = 'Huwelijk of Partnerschap';
		//$order['targetOrganization'] = $requestType['source_organization'];
		$order['targetOrganization'] = '002220647';
		$order['customer'] = 'http://cc.zaakonline.nl'.$contact['_links']['self']['href'];
		
		$order = $commonGroundService->createResource($order, "https://orc.zaakonline.nl/orders");
		$request['properties']['order']= 'https://orc.zaakonline.nl/orders'.$order['_links']['self']['href'];
		
		//$request['target_organization'] = $requestType['source_organization'];
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
	public function submitrequestAction(Session $session, CommonGroundService $commonGroundService, RequestService $requestService)
	{
		$request = $session->get('request');
		$request['status'] = 'submitted';
		
		if($request = $commonGroundService->updateResource($request, "https://vrc.zaakonline.nl/requests/".$request['id'])){
			$session->set('request', $request);
			
			$this->addFlash('success', 'Uw verzoek is ingediend');
		}
		else{
			$this->addFlash('danger', 'Uw verzoek kon niet worden ingediend');
		}
		
		return $this->redirect($this->generateUrl('app_default_slug',["slug"=>"checklist"]));
	}
	
	/**
	 * @Route("request/cancel")
	 */
	public function cancelrequestAction(Session $session, CommonGroundService $commonGroundService, RequestService $requestService)
	{
		$request = $session->get('request');
		$request['status'] = 'cancelled';
		
		if($request = $commonGroundService->updateResource($request, "https://vrc.zaakonline.nl/request/".$request['id'])){
			$session->set('request', $request);
			$this->addFlash('success', 'Uw verzoek is geanuleerd');
		}
		else{
			$this->addFlash('danger', 'Uw verzoek kon niet worden geanuleerd');
		}
		
		return $this->redirect($this->generateUrl('app_default_slug',["slug"=>"checklist"]));
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
		
		$this->addFlash('danger', 'U bent uitgelogd');
		
		return $this->redirect($this->generateUrl('app_default_slug',["slug"=>"trouwen"]));
	}
	
	/**
	 * @Route("/assent/add/{property}")
	 */
	public function assentAddAction(Session $session, Request $httpRequest, $property, RequestService $requestService, ContactService $contactService, AssentService $assentService, CommonGroundService $commonGroundService)
	{
		$requestType = $session->get('requestType');
		$request = $session->get('request');
		$user = $session->get('user');
		
		// First we need to make an new assent
		$assent = [];
		$assent['name'] = 'Instemming huwelijk of partnerschap';
		$assent['description'] = 'U is gevraagd of u wilt instemmen met een huwelijk of partnerschaps';
		//$assent['requester'] = (string) $requestType['sourceOrganization'];
		//$assent['request'] = $request['id'];
		$assent['request'] = 'http://vrc.zaakonline.nl'.$request['_links']['self']['href'];
		$assent['status'] = 'requested';
		$assent['requester'] = $user['burgerservicenummer'];
		
		//$assent= $assentService->createAssent($assent);
		$assent = $commonGroundService->createResource($assent, "https://irc.zaakonline.nl/assents");
		if(!array_key_exists($property ,$request['properties'])){
			$request['properties'][$property] = [];
		}
		$request['properties'][$property][] = 'http://irc.zaakonline.nl'.$assent['_links']['self']['href'];
		
		$request = $requestService->updateRequest($request);
		
		$session->set('requestType',false);
		$session->set('request',false);
		$session->set('user',false);
		
		return $this->redirect($this->generateUrl('app_default_assentlogin',["id"=>$assent["id"]]));
	}
	
	/**
	 * @Route("/assent/{id}")
	 */
	public function assentLoginAction(Session $session, Request $httpRequest, $id, RequestService $requestService, RequestTypeService $requestTypeService, CommonGroundService $commonGroundService, BRPService $brpService, AssentService $assentService, ContactService $contactService, SjabloonService $sjabloonService)
	{
		// We might have a returning user
		$user = $session->get('user');
		
		// Lets first see if we have a login
		$bsn = $httpRequest->request->get('bsn');
		if(!$bsn){
			$bsn =  $httpRequest->query->get('bsn');
		}
		if(!$bsn && !$user){
			// No login suplied so redirect to digispoof
			//return $this->redirect('http://digispoof.zaakonline.nl?responceUrl='.urlencode($httpRequest->getScheme() . '://' . $httpRequest->getHttpHost().$httpRequest->getBasePath()));
			return $this->redirect('http://digispoof.zaakonline.nl?responceUrl='.$httpRequest->getScheme() . '://' . $httpRequest->getHttpHost().$httpRequest->getBasePath().$this->generateUrl('app_default_assentlogin',["id"=>$id]));
		}
		
		
		// if we have a login, lets do a login
		if($bsn && $persoon = $brpService->getPersonOnBsn($bsn)){
			$session->set('user', $persoon);
			$user = $session->get('user');
			
			
			$this->addFlash('success', 'Welkom '.$persoon['naam']['voornamen']);
		}
		
		// If we do not have a user at this point we need to error
		if(!$user){
			$this->addFlash('danger', 'U kon helaas niet worden ingelogd');
			return $this->redirect($this->generateUrl('app_default_index'));
		}
		
		$assent = $assentService->getAssent($id);
		$request = $commonGroundService->getResource($assent['request']);
		$session->set('request', $request);
		
		// Lets also set the request type
		$requestType = $requestTypeService->getRequestType($request['request_type']);
		$requestType = $requestService->checkRequestType($request, $requestType);
		$session->set('requestType', $requestType);
		
		// If user is not loged in
		if(!$assent['contact'] && $user){
			
			$contact = [];
			$contact['givenName']= $user['naam']['voornamen'];
			$contact['familyName']= $user['naam']['geslachtsnaam'];
			
			$contact= $contactService->createContact($contact);
			
			$assent['contact'] = 'http://cc.zaakonline.nl'.$contact['_links']['self']['href'];
			$assent['person'] = $user['burgerservicenummer'];
			
			$assent =  $assentService->updateAssent($assent);
		}
		
		// Let render stuff
		
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
	
	
	/**
	 * @Route("/assent/{id}/{status}")
	 */
	public function assentStatusAction(Session $session, Request $httpRequest, $id, $status, RequestService $requestService, RequestTypeService $requestTypeService, CommonGroundService $commonGroundService, BRPService $brpService, AssentService $assentService, ContactService $contactService, SjabloonService $sjabloonService)
	{
		$request = $session->get('request');
		$requestType = $session->get('requestType');
		$user = $session->get('user');
		
		$assent = $assentService->getAssent($id);
		$assent['status'] = $status;
		
		if($assentService->updateAssent($assent)){
			$this->addFlash('success', 'Uw instemmings status is bijgewerkt naar '.$status);
		}
		else{
			$this->addFlash('danger', 'Uw instemmings status kon niet worden bijgewerkt');
		}
		
		return $this->redirect($this->generateUrl('app_default_assentlogin',["id"=>$assent["id"]]));
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
	 * @Route("/update/assent/{id}", methods={"POST"})
	 */
	public function updateAssentAction(Session $session, Request $httpRequest, $id, RequestService $requestService, ContactService $contactService, AssentService $assentService, CommonGroundService $commonGroundService)
	{
		$requestType = $session->get('requestType');
		$request = $session->get('request');
		$user = $session->get('user');
		
		$assent  = $assentService->getAssent($id);
		$contact = $commonGroundService->getResource($assent['contact']);
		
		if($httpRequest->request->get('givenName')){$contact['givenName']= $httpRequest->request->get('givenName');}
		if($httpRequest->request->get('familyName')){$contact['familyName']= $httpRequest->request->get('familyName');}		
		
		$contact['emails'][0]=["name"=>"primary","email"=> $httpRequest->request->get('email')];
		$contact['telephones'][0]=["name"=>"primary","telephone"=> $httpRequest->request->get('telephone')];
		
		if($contact = $commonGroundService->updateResource($contact, $assent['contact'])){
			$this->addFlash('success', $contact['name'].' is bijgewerkt');
		}
		else{
			$this->addFlash('danger', $contact['name'].' kon niet worden bijgewerkt');
		}
		
		return $this->redirect($this->generateUrl('app_default_slug',["slug"=>$request["current_stage"]]));
	}
	
	/**
	 * @Route("/create/assent/{property}", methods={"POST"})
	 */
	public function createAssentAction(Session $session, Request $httpRequest, $property, RequestService $requestService, ContactService $contactService, AssentService $assentService, CommonGroundService $commonGroundService)
	{
		
		$requestType = $session->get('requestType');
		$request = $session->get('request');
		$user = $session->get('user');
		
		if($request && !in_array("ceremonie", $request["properties"])){
			$request["properties"]["ceremonie"] = "huwelijk/partnerschap";
		}
		
		// First we need to create an assent
		$contact = [];
		$contact['givenName']= $httpRequest->request->get('givenName');
		$contact['familyName']= $httpRequest->request->get('familyName');
		$contact['emails']=[];
		$contact['emails'][]=["name"=>"primary","email"=> $httpRequest->request->get('email')];
		$contact['telephones']=[];
		$contact['telephones'][]=["name"=>"primary","telephone"=> $httpRequest->request->get('telephone')];
		$contact= $contactService->createContact($contact);
		
		/* @todo onderstaande gaat een fout gooien als getuigen worden uitgenodigd voordat het huwelijkstype isgeselecteer (ja dat kan) */
		$assent = [];
		$assent['name'] = 'Instemming als '.$property.' bij '.$request["properties"]["ceremonie"];
		$assent['description'] = 'U bent uitgenodigd als '.$property.' voor het '.$request["properties"]["ceremonie"].' van A en B';
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
			if ($subArray['slug'] === $property) {
				$stage = iterator_to_array($subArray);
				break;
			}
		}		
		
		
		// Lets see if an array already exisits for this property
		if(!array_key_exists($stage["name"], $request['properties'])){
			$request['properties'][$stage["name"]] = [];
		}
		
		$request['properties'][$stage["name"]][] = 'http://irc.zaakonline.nl'.$assent['_links']['self']['href'];
		
		if($request = $requestService->updateRequest($request)){
			$request["current_stage"] = $stage["next"];
			$request = $requestService->updateRequest($request);
			$session->set('request', $request);
			
			$requestType = $requestService->checkRequestType($request, $requestType);
			$session->set('requestType', $requestType);
			
			
			$this->addFlash('success', ucfirst($property).' is ingesteld');
			
			if(isset($stage) && array_key_exists("completed", $stage) && $stage["completed"]){
				//$slug = $stage["next"];
				
				$slug = $stage["slug"];
			}
			else{
				$slug = $stage["slug"];
			}
			
		}
		else{
			$this->addFlash('danger', ucfirst($property).' kon niet worden ingesteld');
		}
		
		return $this->redirect($this->generateUrl('app_default_slug',["slug"=>$slug]));
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
			
			if(isset($property) && array_key_exists("completed", $property) && $property["completed"]){
				$slug = $property["next"];
			}
			elseif(isset($stage) && array_key_exists("slug", $stage)){
				$slug = $property["slug"];
			}
			
			return $this->redirect($this->generateUrl('app_default_slug',["slug"=>$slug]));
		}
		else{
			$this->addFlash('danger', ucfirst($slug).' kon niet worden ingesteld');
			return $this->redirect($this->generateUrl('app_default_view',["slug"=>$slug,"id"=>$id]));
		}
	}
	
	/**
	 * @Route("/{slug}/unset/{id}", requirements={"id"=".+"})
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
		if(array_key_exists("plechtigheid", $request['properties']) && $request['properties']["plechtigheid"] == "https://pdc.zaakonline.nl/products/190c3611-010d-4b0e-a31c-60dadf4d1c62"){
			$request['properties']['locatie']="https://pdc.zaakonline.nl/products/7a3489d5-2d2c-454b-91c9-caff4fed897f";
			$request['properties']['ambtenaar']="https://pdc.zaakonline.nl/products/55af09c8-361b-418a-af87-df8f8827984b";
		}
		// hardcode overwrite for "eenvoudig trouwen"
		if(array_key_exists("plechtigheid", $request['properties']) && $request['properties']["plechtigheid"] == "https://pdc.zaakonline.nl/products/16353702-4614-42ff-92af-7dd11c8eef9f"){
			$request['properties']['locatie']="https://pdc.zaakonline.nl/products/7a3489d5-2d2c-454b-91c9-caff4fed897f";
			$request['properties']['ambtenaar']="https://pdc.zaakonline.nl/products/55af09c8-361b-418a-af87-df8f8827984b";
		}
		
		
		if($request = $requestService->updateRequest($request)){
			$request["current_stage"] = $property["next"];
			$request = $requestService->updateRequest($request);
			$session->set('request', $request);
			
			$requestType = $requestService->checkRequestType($request, $requestType);
			$session->set('requestType', $requestType);
			
			// Lets find the stage that we are add
			$arrIt = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($requestType['stages']));
			
			foreach ($arrIt as $sub) {
				$subArray = $arrIt->getSubIterator();
				if ($subArray['slug'] === $slug) {
					$property = iterator_to_array($subArray);
					break;
				}
			}
						
			$this->addFlash('success', ucfirst($slug).' is ingesteld');
			
			if(isset($property) && array_key_exists("completed", $property) && $property["completed"]){
				$slug = $property["next"];
			}
			else{
				$slug = $property["slug"];
			}
			
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
			
			foreach ($arrIt as $sub) {
				$subArray = $arrIt->getSubIterator();
				if ($subArray['slug'] === $slug) {
					$property = iterator_to_array($subArray);
					break;
				}
			}
			
			
			$this->addFlash('success', ucfirst($slug).' is ingesteld');
			
			if(isset($stage) && array_key_exists("completed", $stage) && $stage["completed"]){
				$slug = $stage["next"];
			}
			elseif(isset($stage) && array_key_exists("slug", $stage)){
				$slug = $stage["slug"];
			}
			
			return $this->redirect($this->generateUrl('app_default_slug',["slug"=>$slug]));
		}
		else{
			$this->addFlash('danger', ucfirst($slug).' kon niet worden ingesteld');
			return $this->redirect($this->generateUrl('app_default_slug',["slug"=>$slug]));;
		}
	}
	
	/**
	 * @Route("/", name="app_default_index")
	 * @Route("/{slug}", name="app_default_slug")
	 * @Route("/{slug}/{id}", name="app_default_view")
	 */
	public function viewAction(Session $session, $slug = false, $id = false, SjabloonService $sjabloonService, PdcService $pdcService, Request $httpRequest, CommonGroundService $commonGroundService, RequestService $requestService)
	{
		$variables=[];
		
		// @todo iets metorganisaties en applicaties
		
		// Lets handle a posible login		
		$bsn = $httpRequest->request->get('bsn');
		if($bsn || $bsn =  $httpRequest->query->get('bsn')){
			$user = $commonGroundService->getResource('https://brp.zaakonline.nl/ingeschrevenpersonen/'.$bsn);
			$session->set('user', $user);
			
			//var_dump($user);
		}
		$variables['user']  = $session->get('user');
		
		// Let handle posible request creation
		$requestType= $httpRequest->request->get('requestType');
		if($requestType || $requestType=  $httpRequest->query->get('requestType')){
			
			$requestTypeUri = $requestType;
			$requestType = $commonGroundService->getResource($requestType);
			$session->set('requestType', $requestType);
						
			$request= [];
			$request['request_type'] = $requestTypeUri;
			$request['target_organization']=$requestType['source_organization'];
			$request['submitter']=$variables['user']['burgerservicenummer'];
			$request['status']='incomplete';
			$request['properties']= [];			
			$request = $commonGroundService->createResource($request, 'https://vrc.zaakonline.nl/requests');			
			
			$contact = [];
			$contact['givenName']= $variables['user']['naam']['voornamen'];
			$contact['familyName']= $variables['user']['naam']['geslachtsnaam'];
			$contact= $commonGroundService->createResource($contact, 'https://cc.zaakonline.nl/people');
			
			$assent = [];
			$assent['name'] = 'Instemming huwelijk partnerschp';
			$assent['description'] = 'U bent automatisch toegevoegd aan een  huwelijk/partnerschap omdat u deze zelf heeft aangevraagd';
			$assent['contact'] = 'http://cc.zaakonline.nl'.$contact['@id'];
			$assent['requester'] = $requestType['source_organization'];
			$assent['person'] = $variables['user']['burgerservicenummer'];
			$assent['request'] = 'http://vrc.zaakonline.nl'.$request['@id'];
			$assent['status'] = 'granted';			
			$assent = $commonGroundService->createResource($assent, 'https://irc.zaakonline.nl/assents');			
			
			$request['properties']['partners'][] = 'http://irc.zaakonline.nl'.$assent['@id'];
			$request = $commonGroundService->updateResource($request, 'https://vrc.zaakonline.nl'.$request['@id']);				
			
			$session->set('request', $request);
			
			// If we dont have a user requested slug lets go to the current request stage
			if(!$slug && array_key_exists ("current_stage", $request) && $request["current_stage"] != null){
				$slug = $request["current_stage"];
			}
			elseif(!$slug && $requestType){
				$slug = $requestType['stages'][0]['slug'];
			}
		}
				
		// Lets handle the loading of a request
		$request= $httpRequest->request->get('request');
		if($request || $request =  $httpRequest->query->get('request')){
			$request = $commonGroundService->getResource($request);
			$requestType = $commonGroundService->getResource($request['request_type']);
			$session->set('request', $request);
			$session->set('requestType', $requestType);
			//var_dump($request);
			
			// If we dont have a user requested slug lets go to the current request stage
			if(!$slug && array_key_exists ("current_stage", $request) && $request["current_stage"] != null){
				$slug = $request["current_stage"];
			}
			elseif(!$slug && $requestType){
				$slug = $requestType['stages'][0]['slug'];
			}
		}		
		$variables['request'] = $session->get('request');
		$variables['requestType'] = $session->get('requestType');
		
		
		// Lets handle the loading of a product is we have one
		if($id){
			/*@todo dit zou de commonground service moeten zijn */
			$variables['product'] = $pdcService->getProduct($id);			
		}
		
		if(!$slug){
			/*@todo dit zou uit de standaard settings van de applicatie moeten komen*/
			$slug="trouwen";
		}
		
		if(array_key_exists('request',$variables) && array_key_exists('requestType',$variables) && $variables['request'] && $variables['requestType']){			
			$variables['requestType'] = $requestService->checkRequestType($variables['request'], $variables['requestType']);
		}
		
		/*@todo olld skool overwite variabel maken */
		switch ($slug) {
			case null :
				$slug = 'trouwen';
				break;
			case 'ambtenaar':
				$variables['products']  = $pdcService->getProducts(['groups.id'=>'7f4ff7ae-ed1b-45c9-9a73-3ed06a36b9cc']);
				break;
			case 'locatie':
				$variables['products'] = $pdcService->getProducts(['groups.id'=>'170788e7-b238-4c28-8efc-97bdada02c2e']);
				break;
			case 'plechtigheid':
				$variables['products'] = $pdcService->getProducts(['groups.id'=>'1cad775c-c2d0-48af-858f-a12029af24b3']);
				break;
			case 'extra':
				$variables['products'] = $pdcService->getProducts(['groups.id'=>'f8298a12-91eb-46d0-b8a9-e7095f81be6f']);
				break;
			case 'requests':
				$variables['requests'] = $commonGroundService->getResourceList('http://vrc.zaakonline.nl/requests', ['submitter' => $variables['user']['burgerservicenummer']])["hydra:member"];
				break;
			case 'new-request':
				$variables['requestTypes'] = $commonGroundService->getResourceList('http://vtc.zaakonline.nl/request_types', ['submitter' => $variables['user']['burgerservicenummer']])["hydra:member"];
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
}
