<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


use App\Service\CommonGroundService;
use App\Service\RequestService;

/**
 * @Route("/betalen")
 */
class BetalenController extends AbstractController
{ 
	/**
	* @Route("/")
	*/
	public function indexAction(Session $session,  CommonGroundService $commonGroundService, RequestService $requestService)
	{
		$request= $session->get('request');
		$user = $session->get('user');
		
		$product = null;
		if(array_key_exists('product', $request['properties'])){
			$product=$commonGroundService->getSingle($request['properties']['product']);
			
			//var_dump($product);
		}
		
		return $this->render('betalen/index.html.twig', [
				'request' => $request,
				'user' => $user,
				'product' => $product,
		]);
	}
}
