<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Service\ProductService;
use App\Service\RequestService; 
use App\Service\CommonGroundService;
/**
 * @Route("/ambtenaren")
 */
class AmbtenaarController extends AbstractController
{ 
	/**
	* @Route("/")
	*/
	public function indexAction(Session $session, ProductService $productService,  CommonGroundService $commonGroundService)
	{
		$request= $session->get('request');
		$user = $session->get('user');
		
		// What if we already have an official?
		if($request && isset($request['properties']['ambtenaar']) && $request['properties']['ambtenaar'] ){
			$ambtenaar=$commonGroundService->getSingle($request['properties']['ambtenaar']);
			return $this->redirect($this->generateUrl('app_ambtenaar_view', ['id'=> (int)$ambtenaar['id']]));			
		}		
		
		$products = $productService->getAllFromGroup('7f4ff7ae-ed1b-45c9-9a73-3ed06a36b9cc');
				
		return $this->render('ambtenaar/index.html.twig', [
				'user' => $user,
				'request' => $request,
				'products' => $products,
		]);
	}
	
	/**
	 * @Route("/voor-een-dag")
	 */
	public function vooreendagAction(Session $session)
	{
		$request= $session->get('request');
		$user = $session->get('user');
		
		return $this->render('ambtenaar/voor-dag.html.twig', [
				'user' => $user,
				'request' => $request,
		]);
	}
	
	/**
	 * @Route("/zelfstandig")
	 */
	public function zelfstandigAction(Session $session)
	{
		$request= $session->get('request');
		$user = $session->get('user');
		
		return $this->render('ambtenaar/zelfstandig.html.twig', [
				'user' => $user,
				'request' => $request,
		]);
	}
	
	/**
	 * @Route("/{id}/set")
	 */
	public function setAction(Session $session, $id,  ProductService $productService, RequestService $requestService)
	{
		$request= $session->get('request');
		$user = $session->get('user');
				
		$ambtenaar = $productService->getOne($id);
		$request['properties']['ambtenaar'] = "http://ambtenaren.demo.zaakonline.nl".$ambtenaar["@id"];
		
		if($request = $requestService->updateRequest($request)){
			$session->set('request', $request);
			$this->addFlash('success', 'Uw ambtenaar '.' is uitgenodigd');
			return $this->redirect($this->generateUrl('app_getuigen_index'));
		}
		else{
			$this->addFlash('danger', 'Ambtenaar kon niet worden geanuleerd');
			return $this->redirect($this->generateUrl('app_ambtenaar_index'));
		}
		
		
					
		
	}
	
	/**
	 * @Route("/{id}/unset")
	 */
	public function unsetAction(Session $session, $id, ProductService $productService, RequestService $requestService)
	{
		$request= $session->get('request');
		$user = $session->get('user');
		
		$huwelijk['ambtenaar'] = null;
		if($huwelijkService->updateHuwelijk($huwelijk)){
			$this->addFlash('success', 'Ambtenaar geanuleerd');
			return $this->redirect($this->generateUrl('app_ambtenaar_index'));
		}
		else{
			$this->addFlash('danger', 'Ambtenaar kon niet worden geanuleerd');
			return $this->redirect($this->generateUrl('app_ambtenaar_index'));
		}
		
	}
	
	/**
	 * @Route("/{id}")
	 */
	public function viewAction(Session $session, $id, ProductService $productService)
	{
		$request= $session->get('request');
		$user = $session->get('user');
		
		$product = $productService->getOne($id);
		
		return $this->render('ambtenaar/ambtenaar.html.twig', [
				'user' => $user,
				'request' => $request,
				'product' => $product,
		]);
	}
}
