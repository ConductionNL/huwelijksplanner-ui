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
 * @Route("/extras")
 */
class ExtraController extends AbstractController
{ 
	/**
	* @Route("/")
	*/
	public function indexAction(Session $session, RequestService $requestService)
	{
		$request= $session->get('request');
		$user = $session->get('user');
		
		$products = $productService->getProducts(['group.id'=>'f8298a12-91eb-46d0-b8a9-e7095f81be6f']);
		
		return $this->render('extra/index.html.twig', [
				'request' => $request,
				'user' => $user,
				'products' => $products,
		]);
	}
	
	/**
	 * @Route("/{id}/set")
	 */
	public function setAction(Session $session, $id, ProductService $productService, RequestService $requestService)
	{
		$request= $session->get('request');
		$user = $session->get('user');
		
		//$product= $productService->getOne($id);
		$request['properties']['extras'][] = $id;
		
		//var_dump($product);
		if($request = $requestService->updateRequest($request)){
			$session->set('request', $request);
			$this->addFlash('success', 'Uw extra is toegevoegd');
			return $this->redirect($this->generateUrl('app_betalen_index'));
		}
		else{
			$this->addFlash('danger', 'Uw extra  kon niet worden toegevoegd');
			return $this->redirect($this->generateUrl('app_extra_index'));
		}
	}
	
	/**
	 * @Route("/{id}")
	 */
	public function viewAction(Session $session, $id, ProductService $productService)
	{
		$request= $session->get('request');
		$user = $session->get('user');
		
		$product= $productService->getOne($id);
		
		return $this->render('ambtenaar/ambtenaar.html.twig', [
				'user' => $user,
				'request' => $request,
				'extra' => $product,
		]);
	}
}
