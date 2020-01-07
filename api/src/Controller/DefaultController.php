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
	public function newrequestAction(Session $session, RequestService $requestService, RequestTypeService $requestTypeService, ContactService $contactService, AssentService $assentService)
	{
		
		$user = $session->get('user');
		
		// Okey we don't have ay requests so lets start a marige request
		$request= [];
		$request['requestType']='http://vtc.zaakonline.nl/request_types/5b10c1d6-7121-4be2-b479-7523f1b625f1';
		$request['targetOrganization']='002220647';
		$request['submitter']=$user['burgerservicenummer'];
		$request['status']='incomplete';
		$request['properties']= [];
		
		$request = $requestService->createRequest($request);
		
		$contact = [];
		$contact['given_name']= $user['naam']['voornamen'];
		$contact['family_name']= $user['naam']['geslachtsnaam'];
		//$contact= $contactService->createContact($contact);
		
		$assent = [];
		$assent['name'] = 'Instemming huwelijk partnerschp';
		$assent['description'] = 'U bent automatisch toegevoegd aan een  huwelijk/partnerschap omdat u deze zelf heeft aangevraagd';
		//$assent['contact'] = 'http://cc.zaakonline.nl'.$contact['_links']['self']['href'];
		$assent['person'] = $user['burgerservicenummer'];
		$assent['request'] = $request['id'];
		$assent['status'] = 'granted';
		
		//$assent = $assentService->createAssent($assent);
		if(!array_key_exists('partners',$request['properties'])){
			$request['properties']['partners'] = [];
		}
		//$request['properties']['partners'][] = ['http://irc.zaakonline.nl'.$assent['_links']['self']['href']];
		
		$request = $requestService->updateRequest($request);
		
		$session->set('request', $request);
		
		// Lets also set the request type
		$requestType = $requestTypeService->getRequestType($request['requestType']);
		$session->set('requestType', $requestType);
		
		
		
		// If we have a starting position lets start there
		if(array_key_exists ("currentStage", $request) && $request["currentStage"] != null){
			$start = $request["currentStage"];
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
	 * @Route("request/{id}")
	 */
	public function loadrequestAction(Session $session, RequestService $requestService, RequestTypeService $requestTypeService , $id)
	{
		
		$request = $requestService->getRequestOnId($id);
		$session->set('request', $request);
		
		// Lets also set the request type
		$requestType = $requestTypeService->getRequestType($request['requestType']);
		$session->set('requestType', $requestType);
		
		
		// If we have a starting position lets start there
		if(array_key_exists ("currentStage", $request) && $request["currentStage"] != null){
			$start = $request["currentStage"];
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
		
		if($bsn && $persoon = $brpService->getPersonOnBsn($bsn)){
			//var_dump($persoon);
			$session->set('user', $persoon);
			
			/*
			 $huwelijk = $huwelijkService->getHuwelijkOnPersoon($persoon);
			 $session->set('huwelijk', $huwelijk);
			 */
			if($requests = $requestService->getRequestOnSubmitter($persoon['burgerservicenummer'])){
				return $this->redirect($this->generateUrl('app_default_slug',["slug"=>"requests"]));;
			}
			else{
				// Okey we don't have ay requests so lets start a marige request
				$request= [];
				$request['requestType']='http://vtc.zaakonline.nl/request_types/47577f44-0ede-4655-a629-027f051d2b07';
				$request['targetOrganization']='002220647';
				$request['submitter']=$persoon['burgerservicenummer'];
				$request['status']='incomplete';
				$request['properties']= [];
				$request['properties']['partner1']= $persoon['burgerservicenummer'];
				
				//$request = $requestService->createRequest($request);
				
				$contact = [];
				$contact['given_name']= $persoon['naam']['voornamen'];
				$contact['family_name']= $persoon['naam']['geslachtsnaam'];
				$contact= $contactService->createContact($contact);
				
				$assent = [];
				$assent['name'] = 'Instemming huwelijk partnerschp';
				$assent['description'] = 'U bent automatisch toegevoegd aan een  huwelijk/partnerschap omdat u deze zelf heeft aangevraagd';
				//$assent['contact'] = 'http://cc.zaakonline.nl'.$contact['_links']['self']['href'];
				$assent['person'] = $persoon['burgerservicenummer'];
				$assent['request'] = $request['id'];
				$assent['status'] = 'granted';
				$assent['requester'] = $persoon['burgerservicenummer'];
				
				//$assent= $assentService->createAssent($assent);
				if(!array_key_exists('partners',$request['properties'])){
					$request['properties']['partners'] = [];
				}
				//$request['properties']['partners'][] = ['http://irc.zaakonline.nl'.$assent['_links']['self']['href']];
				
				$request = $requestService->updateRequest($request);
				
				$session->set('request', $request);				
				
				// Lets also set the request type
				$requestType = $requestTypeService->getRequestType($request['requestType']);
				$session->set('requestType', $requestType);
				
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
		
		return $this->redirect($this->generateUrl('app_default_slug',["slug"=>"trouwen"]));
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
		
		switch ($slug) {
			case null :
				$slug = 'trouwen';
				break;
			case 'ambtenaren':
				$variables['products']  = $pdcService->getProducts(['group.id'=>'7f4ff7ae-ed1b-45c9-9a73-3ed06a36b9cc']);
				break;
			case 'locaties':
				$variables['products'] = $pdcService->getProducts(['group.id'=>'170788e7-b238-4c28-8efc-97bdada02c2e']);
				break;
			case 'ceremonies': 
				$variables['roducts'] = $pdcService->getProducts(['group.id'=>'1cad775c-c2d0-48af-858f-a12029af24b3']);
				break;
			case 'extras':
				$variables['products'] = $pdcService->getProducts(['group.id'=>'f8298a12-91eb-46d0-b8a9-e7095f81be6f']);
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
	 * @Route("/{slug}/add/{id}")
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
		if(!$request['properties'][$property["name"]]){
			$request['properties'][$property["name"]] = [];
		}
			
				
		$request['properties'][$property["name"]][] = [$id];
		
		if($request = $requestService->updateRequest($request)){
			$session->set('request', $request);			
						
			$slug = $property["next"];
			
			$this->addFlash('success', ucfirst($slug).' is ingesteld');			
			return $this->redirect($this->generateUrl('app_default_slug',["slug"=>$slug]));
		}
		else{
			$this->addFlash('danger', ucfirst($slug).' kon niet worden ingesteld');
			return $this->redirect($this->generateUrl('app_default_view',["slug"=>$slug,"id"=>$id]));
		}
	}
	
	/**
	 * @Route("/{slug}/set/{id}")
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
		
		$request['properties'][$property["name"]] = [$id];
				
		if($request = $requestService->updateRequest($request)){
			$session->set('request', $request);
			
			// Lets find the stage that we are add
			$arrIt = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($requestType['stages']));
			
			$slug = $property["next"];
			
			$this->addFlash('success', ucfirst($slug).' is ingesteld');
			return $this->redirect($this->generateUrl('app_default_slug',["slug"=>$slug]));
		}
		else{			
			$this->addFlash('danger', ucfirst($slug).' kon niet worden ingesteld');
			return $this->redirect($this->generateUrl('app_default_view',["slug"=>$slug,"id"=>$id]));
		}
	}
	
	/**
	 * @Route("/{slug}/unset/{id}")
	 */
	public function unsetAction(Session $session, $id, RequestService $requestService)
	{
		$requestType = $session->get('requestType');
		$request= $session->get('request');
		$user = $session->get('user');
		
		if(is_array($request['properties'][$slug])){			
			$key = array_search($id, $request['properties']); 
			unset($request['properties'][$slug][$key]);
		}
		else{
			$request['properties'][$slug] = null;
		}
		
		if($request = $requestService->updateRequest($request)){
			$this->addFlash('success', ucfirst($slug).' geanuleerd');
			return $this->redirect($this->generateUrl('app_ambtenaar_index'));
		}
		else{
			$this->addFlash('danger', ucfirst($slug).' kon niet worden geanuleerd');
			return $this->redirect($this->generateUrl('app_ambtenaar_index'));
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
		
		$variables = ["request"=>$request,"user"=>$user,"products"=>$products];
		
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
