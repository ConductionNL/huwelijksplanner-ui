<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Service\ProductService;
use App\Service\RequestService; 
/**
 * @Route("/huwelijk")
 */
class HuwelijkController extends AbstractController
{ 
	/**
	* @Route("/")
	*/
	public function indexAction(Session $session, ProductService $productService)
	{
		$request= $session->get('request');
		$user = $session->get('user');
		
		
		return $this->render('huwelijk/index.html.twig', [
				'request' => $request,
				'user' => $user
		]);
	}
	
	/**
	 * @Route("/type/{type}")
	 */
	public function typeAction(Session $session, RequestService $requestService, $type)
	{
		$request= $session->get('request');
		$user = $session->get('user');
		
		$request['properties']['type'] = $type;
		if($request = $requestService->updateRequest($request)){
			$session->set('request', $request);
			$this->addFlash('success', 'Type '.$type.' geselecteerd');
			return $this->redirect($this->generateUrl('app_partner_index'));		
		}
		else{
			$this->addFlash('danger', 'Type '.$type.' kon niet worden geselecteerd');
			return $this->redirect($this->generateUrl('app_huwelijk_index'));		
		}
	}
	
	
	/**
	 * @Route("/unset")
	 */
	public function unsetAction(Session $session, RequestService $requestService)
	{
		$request= $session->get('request');
		$user = $session->get('user');
		
		unset($request['properties']['type']);
		if($request = $requestService->updateRequest($request)){
			$session->set('request', $request);
			$this->addFlash('success', 'Plechtigheid verwijderd');
			return $this->redirect($this->generateUrl('app_product_index'));
		}
		else{
			$this->addFlash('danger', 'Plechtigheid kon niet worden verwijderd');
			return $this->redirect($this->generateUrl('app_product_index'));
		}
		
	}
	
}
