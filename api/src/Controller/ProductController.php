<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


use App\Service\ProductService;
use App\Service\HuwelijkService;
use App\Service\CommonGroundService;
use App\Service\RequestService; 

/**
 * @Route("/producten")
 */
class ProductController extends AbstractController
{ 
	/**
	* @Route("/")
	*/
	public function indexAction(Session $session, ProductService $productService,  CommonGroundService $commonGroundService)
	{
		$request = $session->get('request');
		$user = $session->get('user');
		
		$producten = $productService->getAll();
		
		$ceremonie= null;
		if($request && isset($request['properties']['ceremonie']) && $request['properties']['ceremonie']){
			$ceremonie=$commonGroundService->getSingle($request['properties']['ceremonie']);
		}
		
		return $this->render('product/index.html.twig', [
				'user' => $user,
				'request' => $request,
				'producten' => $producten,
				'ceremonie' => $ceremonie,
		]);
	}
		
	/**
	 * @Route("/{id}/set")
	 */
	public function setAction(Session $session, $id, ProductService $productService, HuwelijkService $huwelijkService, RequestService $requestService)
	{
		$request = $session->get('request');
		$user = $session->get('user');
		
		$product= $productService->getOne($id);		
		
		$request['properties']['product'] = "http://producten-diensten.demo.zaakonline.nl".$product["@id"];	
		if($id == 1 || $id == 2){
			$request['properties']['locatie'] = "http://locaties.demo.zaakonline.nl/locaties/1";	
			$request['properties']['ambtenaar'] = "http://ambtenaren.demo.zaakonline.nl/ambtenaren/4";	
		}
		
		
		if($request = $requestService->updateRequest($request)){
			$session->set('request', $request);
			$this->addFlash('success', 'Uw plechtigheid '.$product['naam'].' ingesteld');
			return $this->redirect($this->generateUrl('app_datum_index'));
		}
		else{
			$this->addFlash('danger', 'Uw plechtigheid kon niet worden ingesteld');
			return $this->redirect($this->generateUrl('app_product_index'));
		}
		
	}
	
	/**
	 * @Route("/{id}")
	 */
	public function viewAction(Session $session, $id, ProductService $productService, RequestService $requestService)
	{
		$request = $session->get('request');
		$user = $session->get('user');
		
		$product= $productService->getOne($id);
		
		return $this->render('product/product.html.twig', [
				'user' => $user,
				'request' => $request,
				'product' => $product,
		]);
	}
}
