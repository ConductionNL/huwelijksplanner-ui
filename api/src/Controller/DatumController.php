<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Service\RequestService; 

/**
 * @Route("/datum")
 */
class DatumController extends AbstractController
{ 
	/**
	* @Route("/")
	*/
	public function indexAction(Session $session, Request $httprequest, RequestService $requestService)
	{
		$request= $session->get('request');
		$user = $session->get('user');
		
		/* @todo we should turn this into symfony form */
		if ($httprequest->isMethod('POST') && $httprequest->request->get('datum')) {
			
			//var_dump($request->request->get('datum'));
			
			$dateArray = (explode(" ", $httprequest->request->get('datum')));
			$date = strtotime($dateArray[1].' '.$dateArray[2].' '.$dateArray[3]);
			$postdate = date('Y-m-d',$date);
			$displaydate = date('d-m-Y',$date);
			
			
			$request['properties']['datum'] = $displaydate;
			if($request = $requestService->updateRequest($request)){
				$session->set('request', $request);
				$this->addFlash('success', 'Uw datum '.$displaydate.' is ingesteld');
				return $this->redirect($this->generateUrl('app_locatie_index'));
			}
			else{
				$this->addFlash('danger', 'Uw datum kon niet worden ingesteld');
				return $this->redirect($this->generateUrl('app_datum_index'));
			}
				
		}
		return $this->render('datum/index.html.twig', [
				'request' => $request,
				'user' => $user,
		]);
	}
	
	/**
	 * @Route("/set")
	 */
	public function setAction(Session $session, RequestService $requestService)
	{
		$request= $session->get('request');
		$user = $session->get('user');
		
		
		return $this->redirect($this->generateUrl('app_locatie_index'));
	}
}
