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
 * @Route("/locaties")
 */
class LocatieController extends AbstractController
{ 
	/**
	* @Route("/")
	*/
	public function indexAction(Session $session, ProductService $productService,  CommonGroundService $commonGroundService)
	{
		$request= $session->get('request');
		$user = $session->get('user');
				
		$locatie = null;
		// What if we already have an official?
		if($request && isset($request['properties']['locatie']) && $request['properties']['locatie'] ){
			$locatie=$commonGroundService->getSingle($request['properties']['locatie']);
			return $this->redirect($this->generateUrl('app_locatie_view', ['id'=> (int)$locatie['id']]));
		}	
		
		$products = $productService->getProducts(['group.id'=>'170788e7-b238-4c28-8efc-97bdada02c2e']);
		
		return $this->render('ambtenaar/index.html.twig', [
				'user' => $user,
				'request' => $request,
				'products' => $products,
		]);
	}
	
	/**
	 * @Route("/{id}/set")
	 */
	public function setAction(Session $session, $id,  ProductService $productService, RequestService $requestService)
	{
		$request= $session->get('request');
		$user = $session->get('user');
		
		$locatie = $locatieService->getOne($id);
		$request['properties']['locatie'] ="http://locaties.demo.zaakonline.nl".$locatie["@id"];
		
		if($request = $requestService->updateRequest($request)){
			$session->set('request', $request);
			$this->addFlash('success', 'Uw locatie '.$locatie['naam'].' is toegevoegd');
			return $this->redirect($this->generateUrl('app_ambtenaar_index'));
		}
		else{
			$this->addFlash('danger', 'Locatie kon niet worden toegeveogd');
			return $this->redirect($this->generateUrl('app_locatie_index'));
		}
		
	}
	
	/**
	 * @Route("/{id}/unset")
	 */
	public function unsetAction(Session $session, $id,  ProductService $productService, RequestService $requestService)
	{
		$request= $session->get('request');
		$user = $session->get('user');
		
		
		$huwelijk['locatie'] = null;		
		if($huwelijkService->updateHuwelijk($huwelijk)){
			$this->addFlash('success', 'Locatie '.$locatie['naam'].' is ingesteld');
			return $this->redirect($this->generateUrl('app_locatie_index'));
		}
		else{
			$this->addFlash('danger', 'Locatie '.$locatie['naam'].' kon niet worden ingesteld');
			return $this->redirect($this->generateUrl('app_locatie_index'));
		}
		
	}
	
	/**
	 * @Route("/{id}")
	 */
	public function viewAction(Session $session, $id,  ProductService $productService,  CommonGroundService $commonGroundService)
	{
		$request= $session->get('request');
		$user = $session->get('user');
		
		
		$product = $productService->getOne($id);
				
		return $this->render('locatie/locatie.html.twig', [
				'user' => $user,
				'request' => $request,
				'product' => $product,
		]);
	}
}
