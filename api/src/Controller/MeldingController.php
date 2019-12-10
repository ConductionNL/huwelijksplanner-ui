<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Service\RequestService; 
/**
 * @Route("/melding")
 */
class MeldingController extends AbstractController
{ 
	/**
	* @Route("/")
	*/
	public function indexAction(Session $session, RequestService $requestService)
	{
		$request= $session->get('request');
		$user = $session->get('user');
		
		return $this->render('melding/index.html.twig', [
				'request' => $request,
				'user' => $user,
		]);
	}
}
