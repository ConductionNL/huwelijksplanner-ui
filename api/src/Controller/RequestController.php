<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Service\RequestService;
use App\Service\RequestTypeService;
use App\Service\AssentService; 
/**
 * @Route("/requests")
 */
class RequestController extends AbstractController
{ 
	/**
	* @Route("/")
	*/
	public function indexAction(Session $session, RequestService $requestService, RequestTypeService $requestTypeService)
	{
		$user = $session->get('user');
		
		$requests = $requestService->getRequestOnSubmitter($user['burgerservicenummer']);
		
		foreach($requests as $key => $value){
			$requests[$key]['request_typeObject'] = $requestTypeService->getRequestTypeOnUri($value['request_type']);
		}
		
		return $this->render('home/requests.html.twig', [
				'user' => $user,
				'requests' => $requests
		]);
	}
	
	/**
	 * @Route("/new")
	 */
	public function newAction(Session $session, RequestService $requestService, AssentService $assentService)
	{
		$user = $session->get('user');
		
		// Okey we don't have ay requests so lets start a marige request
		$request= [];
		$request['request_type']='http://vtc.zaakonline.nl/request_types/16f43fb8-735c-42bc-8918-02388cffa229';
		$request['target_organization']='002220647';
		$request['submitter']=$user['burgerservicenummer'];
		$request['status']='incomplete';
		$request['properties']= [];
		
		$request = $requestService->createRequest($request);
		
		$assent = [];
		$assent['name'] = 'Instemming huwelijk partnerschap';
		$assent['description'] = 'U bent automatisch toegevoegd aan een  huwelijk/partnerschap omdat u deze zelf heeft aangevraagd';
		$assent['person'] = $user['burgerservicenummer'];
		$assent['request'] = $request['id'];
		$assent['status'] = 'granted';
		$assent['requester'] = $user['burgerservicenummer'];
		
		$assent = $assentService->createAssent($assent);
		
		$request['properties']['partner1']= 'http://irc.zaakonline.nl'.$assent['_links']['self']['href'];
		
		$request = $requestService->updateRequest($request);
		
		$session->set('request', $request);
		
		$response = $this->forward('App\Controller\HuwelijkController::indexAction');
		return $response;
	}
	/**
	 * @Route("/{id}")
	 */
	public function continueAction(Session $session, RequestService $requestService, $id)
	{
		
		$request = $requestService->getRequestOnId($id);
		$session->set('request', $request);
		
		$response = $this->forward('App\Controller\HuwelijkController::indexAction');
		return $response;
	}
}
