<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Service\HuwelijkService;
use App\Service\BRPService; 
use App\Service\RequestService;
use App\Service\AssentService; 
use App\Service\ContactService; 

class HomeController extends AbstractController
{ 	
	
	/**
	* @Route("/")
	*/
	public function indexAction(Session $session)
	{
		$request= $session->get('request');
		$user = $session->get('user');
		$person = $session->get('person');
		
		//var_dump($huwelijk);
		
		return $this->render('home/index.html.twig', [
				'request' => $request,
				'user' => $user,
		]);
	}
	
	/**
	 * @Route("/login")
	 */
	public function loginAction(Session $session, Request $request, HuwelijkService $huwelijkService, BRPService $brpService, RequestService $requestService,  ContactService $contactService, AssentService $assentService)
	{
		$bsn = $request->request->get('bsn');
		if(!$bsn){
			$bsn =  $request->query->get('bsn');
		}
		
		if($persoon = $brpService->getPersonOnBsn($bsn)){
			//var_dump($persoon);
			$session->set('user', $persoon);	
			
			/*
			$huwelijk = $huwelijkService->getHuwelijkOnPersoon($persoon);
			$session->set('huwelijk', $huwelijk);
			*/
			if($requests = $requestService->getRequestOnSubmitter($persoon['burgerservicenummer'])){
				$response = $this->forward('App\Controller\RequestController::indexAction');
				return $response;
			}
			else{
				// Okey we don't have ay requests so lets start a marige request
				$request= [];
				$request['request_type']='http://vtc.zaakonline.nl/request_types/47577f44-0ede-4655-a629-027f051d2b07'; 
				$request['target_organization']='002220647';
				$request['submitter']=$persoon['burgerservicenummer'];
				$request['status']='incomplete'; 				
				$request['properties']= [];
				$request['properties']['partner1']= $persoon['burgerservicenummer']; 
				
				$request = $requestService->createRequest($request);
				
				$contact = [];
				$contact['given_name']= $persoon['naam']['voornamen'];
				$contact['family_name']= $persoon['naam']['geslachtsnaam'];				
				$contact= $contactService->createContact($contact);
				
				$assent = [];
				$assent['name'] = 'Instemming huwelijk partnerschp';
				$assent['description'] = 'U bent automatisch toegevoegd aan een  huwelijk/partnerschap omdat u deze zelf heeft aangevraagd';
				$assent['contact'] = 'http://cc.zaakonline.nl'.$contact['_links']['self']['href'];
				$assent['person'] = $persoon['burgerservicenummer'];
				$assent['request'] = $request['id'];
				$assent['status'] = 'granted';
				$assent['requester'] = $persoon['burgerservicenummer'];
				
				$assent= $assentService->createAssent($assent);
				
				$request['properties']['partner1']= 'http://irc.zaakonline.nl'.$assent['_links']['self']['href']; 				
				
				$request = $requestService->updateRequest($request);
				
				$session->set('request', $request);	
			}
			$this->addFlash('success', 'Welkom '.$persoon['naam']['voornamen']);
		}
		else{
			$this->addFlash('danger', 'U kon helaas niet worden ingelogd');		
		}
				
		$response = $this->forward('App\Controller\HuwelijkController::indexAction');		
		return $response;
	}
	
	/**
	 * @Route("/logout")
	 */
	public function logoutAction(Session $session)
	{
	    $session->set('request',false);
	    $session->set('user',false);
	    $session->set('employee',false);
	    $session->set('contact',false);
		
		$response = $this->forward('App\Controller\HomeController::indexAction');
		return $response;
	}
	
	/**
	 * @Route("/data")
	 */
	public function dataAction(Session $session)
	{
		$huwelijk = $session->get('huwelijk');
		$user = $session->get('user');
		$person = $session->get('person');
		
		$response = new JsonResponse($huwelijk);
		return $response;
	}
}
