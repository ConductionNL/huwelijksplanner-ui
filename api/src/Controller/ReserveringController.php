<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Service\HuwelijkService;
use App\Service\CommonGroundService;
use App\Service\RequestService;
use App\Service\AssentService; 
/**
 * @Route("/reservering")
 */
class ReserveringController extends AbstractController
{ 
	/**
	* @Route("/")
	*/
	public function indexAction(Session $session,  CommonGroundService $commonGroundService, AssentService $assentService)
	{
		$request= $session->get('request');
		$user = $session->get('user');
		
		// What if we already have an official?
		$locatie= null;
		$ambtenaar= null;
		$ceremonie= null;
		$getuigen = [];
		$partners= [];
		
		
		if( array_key_exists('partner1', $request['properties']) && $request['properties']['partner1']){
			$partners[] = $assentService->getAssentOnUri($request['properties']['partner1']);
		}
		if( array_key_exists('partner2', $request['properties']) && $request['properties']['partner2']){
			$partners[] = $assentService->getAssentOnUri($request['properties']['partner2']);			
		}
		
		$getuigen = [];
		if(array_key_exists('getuigenPartner1', $request['properties'])){
			$getuigen = array_merge($getuigen, $request['properties']['getuigenPartner1']);
		}
		if(array_key_exists('getuigenPartner2', $request['properties'])){
			$getuigen = array_merge($getuigen, $request['properties']['getuigenPartner2']);
		}
		
		foreach ($getuigen as $key => $value){
			$getuigen[$key] = $assentService->getAssentOnUri($value);
		}
		
		return $this->render('reservering/index.html.twig', [
				'request' => $request,
				'user' => $user,
				'partners' => $partners,
				'getuigen' => $getuigen,
				'ceremonie' => $ceremonie,
				'locatie' => $locatie,
				'ambtenaar' => $ambtenaar
		]);
	}
	
	/**
	 * @Route("/send")
	 */
	public function sendAction(Session $session, HuwelijkService $huwelijkService)
	{
		$request= $session->get('request');
		$user = $session->get('user');		
		
		$request['status'] = 'submitted';
				
		if($request = $requestService->updateRequest($request)){
			$this->addFlash('success', 'Uw reservering is verzonden');
			return $this->redirect($this->generateUrl('app_melding_index'));
		}
		else{
			$this->addFlash('danger', 'Uw reservering kon niet worden verzonden');
			return $this->redirect($this->generateUrl('app_reservering_index'));
		}		
		
		
	}
	
	/**
	 * @Route("/cancel")
	 */
	public function cancelAction(Session $session, HuwelijkService $huwelijkService)
	{
		
		$request= $session->get('request');
		$user = $session->get('user');
		
		$request['status'] = 'withdrawn';
		
		if($request = $requestService->updateRequest($request)){
			$session->set('request', $request);
			$this->addFlash('success', 'Uw reservering is geanuleerd');
			return $this->redirect($this->generateUrl('app_reservering_index'));
		}
		else{
			$session->set('request', $request);
			$this->addFlash('danger', 'Uw reservering kon niet worden geanuleerd');
			return $this->redirect($this->generateUrl('app_reservering_index'));
		}		
	}
}
