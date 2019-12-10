<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use MessageBird\Client as MessageBird;

use App\Service\HuwelijkService;
use App\Service\PersonenService;
use App\Service\CommonGroundService;
use App\Service\NotificationService;
use App\Service\RequestService;
use App\Service\ContactService;
use App\Service\AssentService; 

/**
 * @Route("/getuigen")
 */
class GetuigenController extends AbstractController
{ 
	/**
	* @Route("/")
	*/
	public function indexAction(Session $session, Request $httpRequest, ContactService $contactService, AssentService $assentService, PersonenService $personenServices,  CommonGroundService $commonGroundService, NotificationService $notificationService)
	{
		$request= $session->get('request');
		$user = $session->get('user');
		
		/* @todo we should turn this into symfony form */
		if ($httpRequest->isMethod('POST')) {
			// Opstellen bericht
			//$key = "KlemtSTIvVWVQRS0QZJIF9qB0";
			//$messageBird = new MessageBird($key, new \MessageBird\Common\HttpClient(MessageBird::ENDPOINT, 10, 10));
			
			$contact = [];
			$contact['given_name']  = $httpRequest->request->get('voornamen');
			$contact['family_name']  = $httpRequest->request->get('geslachtsnaam');
			$contact['telephones'] = [];
			$contact['emails'] = [];
			if($httpRequest->request->get('telefoonnummer')){$contact['telephones'][]['telephone']  = $httpRequest->request->get('telefoonnummer');}
			if($httpRequest->request->get('emailadres')){$contact['emails'][]['email']  = $httpRequest->request->get('emailadres');}
						
			if (!filter_var($httpRequest->request->get('emailadres'), FILTER_VALIDATE_EMAIL)) {				
				$this->addFlash('danger', 'Ongeldig email adres '.$httpRequest->request->get('emailadres'));
				return $this->redirect($this->generateUrl('app_getuigen_index'));
			}
			
			$contact = $contactService->createContact($contact);
			
			$assent = [];
			$assent['name'] = 'Instemming huwelijk getuigen';
			$assent['description'] = 'U bent gevraag om in te stemmen met een rol als getuigen in een huwelijk/partnerschap';
			$assent['contact'] = 'http://cc.zaakonline.nl'.$contact['_links']['self']['href'];
			$assent['request'] = $request['id'];
			$assent['status'] = 'requested';
			$assent['requester'] = $user['burgerservicenummer'];
			
			$assent = $assentService->createAssent($assent);
			
			//asad
			$request['properties'][$httpRequest->request->get('partner')][] = 'http://irc.zaakonline.nl'.$assent['_links']['self']['href'];
			
			$session->set('request', $request);			
			
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
			
			$persoon = $personenServices->create($persoon);
			*/
			
			/*
			
			if(!$huwelijk['getuigen']){
				$huwelijk['getuigen']=[];
			}
			
			$huwelijk['getuigen'][] = 'http://personen.demo.zaakonline.nl'.$persoon["@id"];
			
			if($huwelijkService->updateHuwelijk($huwelijk)){
				$this->addFlash('success', 'Uw getuige '.$persoon['naam']['voornamen'].' '.$persoon['naam']['geslachtsnaam'].' is uitgenodigd');
				
				
				if(count($huwelijk['getuigen'])>=4){
					return $this->redirect($this->generateUrl('app_extra_index'));					
				}
				return $this->redirect($this->generateUrl('app_getuigen_index'));
			}
			else{
				$this->addFlash('danger', 'Getuige kon niet worden uitgenodigd');
				return $this->redirect($this->generateUrl('app_getuigen_index'));
			}
			*/
		}
		
		// Ophalen van gegevens getuigen
		$getuigen = [];
		if($request['properties'] && array_key_exists('getuigenPartner1', $request['properties'])){
			$getuigen = array_merge($getuigen, $request['properties']['getuigenPartner1']);
		}
		if($request['properties'] && array_key_exists('getuigenPartner2', $request['properties'])){
			$getuigen = array_merge($getuigen, $request['properties']['getuigenPartner2']);
		}
		
		foreach ($getuigen as $key => $value){
			$getuigen[$key] = $assentService->getAssentOnUri($value);
		}
		
		return $this->render('getuigen/index.html.twig', [
				'request' => $request,
				'user' => $user,
				'getuigen' => $getuigen,
		]);
	} 
}
