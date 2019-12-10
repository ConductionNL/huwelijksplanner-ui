<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use MessageBird\Client as MessageBird;

use App\Service\BRPService; 
use App\Service\RequestService;
use App\Service\AssentService;
use App\Service\ContactService; 


use App\Service\PersonenService;


use App\Service\CommonGroundService;
use App\Service\NotificationService;


/**
 * @Route("/partner")
 */
class PartnerController extends AbstractController
{ 
	/**
	* @Route("/")
	*/
	public function indexAction(Session $session, Request $httpRequest, BRPService $brpService, RequestService $requestService, ContactService $contactService, AssentService $assentService, PersonenService $personenService,  CommonGroundService $commonGroundService, NotificationService $notificationService)
	{
		$request = $session->get('request');
		$user = $session->get('user');
		
		// Lets get the partners
		$partner1 = false;
		$partner2 = false;
		
		if( array_key_exists('partner1', $request['properties']) && $request['properties']['partner1']){
			$partner1 = $assentService->getAssentOnUri($request['properties']['partner1']);
		}
		if( array_key_exists('partner2', $request['properties']) && $request['properties']['partner2']){
			$partner2 = $assentService->getAssentOnUri($request['properties']['partner2']);
			
		}
		/*
		if(!array_key_exists('partner1', $request['properties']) && array_key_exists('partner2', $request['properties'])){
			$request['properties']['partner1'] = $user['burgerservicenummer'];
			$requestService->updateRequest($request);
			$session->set('request', $request);
		}
		*/
		
		/* @todo we are going to ignore the whole contacten and instemmingen story right now*/		
		if ($httpRequest->isMethod('POST')) {
			
			$contact = [];
			$contact['given_name']  = $httpRequest->request->get('voornamen');
			$contact['family_name']  = $httpRequest->request->get('geslachtsnaam');
			$contact['telephones'] = [];
			$contact['emails'] = [];
			if($httpRequest->request->get('telefoonnummer')){$contact['telephones'][]['telephone']  = $httpRequest->request->get('telefoonnummer');}
			if($httpRequest->request->get('emailadres')){$contact['emails'][]['email']  = $httpRequest->request->get('emailadres');}
			$contact = $contactService->createContact($contact);
			
			$assent = [];
			$assent['name'] = 'Instemming huwelijk partnerschap';
			$assent['description'] = 'U bent gevraag om in te stemmen met een rol als partner in een huwelijk/partnerschap';
			$assent['contact'] = 'http://cc.zaakonline.nl'.$contact['_links']['self']['href'];
			$assent['request'] = $request['id'];
			$assent['description'] = 'U bent gevraag om in te stemmen met een rol als partner in een huwelijk/partnerschap';
			$assent['status'] = 'requested';
			$assent['requester'] = $user['burgerservicenummer'];
			
			$assent = $assentService->createAssent($assent);
			
			if(!array_key_exists('partner1', $request['properties'])){
			    $request['properties']['partner1'] = 'http://irc.zaakonline.nl'.$assent['_links']['self']['href'];
			}
			if(!array_key_exists('partner2', $request['properties'])){
			    $request['properties']['partner2'] = 'http://irc.zaakonline.nl'.$assent['_links']['self']['href'];
			}
			
			// Lets update the request
			$request= $requestService->updateRequest($request);
			$session->set('request', $request);			
			
			$this->addFlash('success', 'Uw partner '.$contact['given_name'].' '.$contact['family_name'].' is uitgenodigd');
			
			return $this->redirect($this->generateUrl('app_product_index'));
			
			/*
			 if($persoon['telefoonnummer']){
			 $notificationService->sendSMS('Gefeliciteerd u bent uitgenodigd als partner voor een huwelijk. U kunt via deze link bevestigen http://utrecht.trouwplanner.online/token/adf32t343rfa',$persoon['telefoonnummer']);
			 }
			/*
			// Opstellen bericht
			$key = "KlemtSTIvVWVQRS0QZJIF9qB0";
			$messageBird = new MessageBird($key, new \MessageBird\Common\HttpClient(MessageBird::ENDPOINT, 10, 10));
			
			$persoon['naam']['voornamen'] = $request->request->get('voornamen');
			$persoon['naam']['geslachtsnaam'] = $request->request->get('geslachtsnaam');
			$persoon['emailadres'] = $request->request->get('emailadres');
			$persoon['telefoonnummer'] = $request->request->get('telefoonnummer');
			
			if (!filter_var($persoon['emailadres'], FILTER_VALIDATE_EMAIL)) {
				$this->addFlash('danger', 'Ongeldig email adres '.$persoon['emailadres']);
				return $this->redirect($this->generateUrl('app_partner_index'));
			}
			/*
			if($persoon['telefoonnummer'])
			{
				try {
					$Lookup = $messageBird->lookup->read($persoon['telefoonnummer']);
					//var_dump($Lookup);
				} catch (Exception $e) {
					$this->addFlash('danger', 'Ongeldig telefoonnummer '.$persoon['telefoonnummer'].' probeer een telefoon nummer met alleen cijfers en voorgegaan door 31');
					return $this->redirect($this->generateUrl('app_getuigen_index'));
				}
			}
			$persoon = $personenService->create($persoon);
						
			$huwelijk['partners'][] = 'http://personen.demo.zaakonline.nl'.$persoon["@id"];
			
			if($huwelijkService->updateHuwelijk($huwelijk)){
				$this->addFlash('success', 'Uw partner '.$persoon['naam']['voornamen'].' '.$persoon['naam']['geslachtsnaam'].' is uitgenodigd');
				
				/*
				if($persoon['telefoonnummer']){
					$notificationService->sendSMS('Gefeliciteerd u bent uitgenodigd als partner voor een huwelijk. U kunt via deze link bevestigen http://utrecht.trouwplanner.online/token/adf32t343rfa',$persoon['telefoonnummer']);
				}
				return $this->redirect($this->generateUrl('app_product_index'));
			}
			else{
				$this->addFlash('danger', 'Partner kon niet worden uitgenodigd');
			}
			*/
		}
				
		return $this->render('partner/index.html.twig', [
				'request' => $request,
				'user' => $user,
				'partner1' => $partner1,
				'partner2' => $partner2,
		]);
	}
	
	/**
	 * @Route("/change_partner")
	 */
	public function changepartnerAction(Session $session , RequestService $requestService, AssentService $assentService)
	{
	    $request = $session->get('request');
	    $user = $session->get('user');
	   
	    $assent = [];
	    $assent['name'] = 'Instemming huwelijk partnerschap';
	    $assent['description'] = 'U bent gevraag om in te stemmen met een rol als partner in een huwelijk/partnerschap';
	    $assent['request'] = $request['id'];
	    $assent['status'] = 'requested';
	    $assent['requester'] = $user['burgerservicenummer'];
	    
	    $assent = $assentService->createAssent($assent);
	    
	    if(!array_key_exists('partner1', $request['properties'])){
	        $request['properties']['partner1'] = 'http://irc.zaakonline.nl/'.$assent['_links']['self']['href'];
	    }
	    if(!array_key_exists('partner2', $request['properties'])){
	        $request['properties']['partner2'] = 'http://irc.zaakonline.nl/'.$assent['_links']['self']['href'];
	    } 
	    
	    // Lets update the request
	    $request= $requestService->updateRequest($request);
	    $session->set('request', $request);
	    
	    // Log out the current user
	    $session->remove('user');
	    $session->remove('$request'); 
	    
	    return $this->redirect('http://digispoof.zaakonline.nl?responceUrl=http://ta.zaakonline.nl'.$this->generateUrl('app_assent_assent', ['token' => $assent['id']]));
	}
	
	/**
	 * @Route("/unset_partner")
	 */
	public function unsetpartnerAction(Session $session , RequestService $requestService)
	{
		$request= $session->get('request');
		$user = $session->get('user');
		
		unset($request['properties']['partner2']);
		if($request = $requestService->updateRequest($request)){
			$session->set('request', $request);
			$this->addFlash('success', 'Partner verwijderd');
			return $this->redirect($this->generateUrl('app_partner_index'));
		}
		else{
			$this->addFlash('danger', 'Partner kon niet worden verwijderd');
			return $this->redirect($this->generateUrl('app_partner_index'));
		}
		
		return $this->redirect($this->generateUrl('app_partner_index'));
	}
	
	/**
	 * @Route("/updateUser")
	 */
	public function updateuserAction(Session $session , Request $httpRequest, RequestService $requestService, AssentService $assentService,ContactService $contactService)
	{
		$request= $session->get('request');
		$user = $session->get('user');
				
		$partner1 = $assentService->getAssentOnUri($request['properties']['partner1']);		
		if($httpRequest->request->get('telefoonnummer')){$partner1['contactObject']['telephones'][]['telephone']  = $httpRequest->request->get('telefoonnummer');}
		if($httpRequest->request->get('emailadres')){$partner1['contactObject']['emails'][]['email']  = $httpRequest->request->get('emailadres');}
		$contact = $contactService->updateContact($partner1['contactObject']);
		
		return $this->redirect($this->generateUrl('app_partner_index'));
	}
}
