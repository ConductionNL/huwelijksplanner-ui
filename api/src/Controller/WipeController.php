<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Service\PersonenService;
use App\Service\HuwelijkService;
/**
 * @Route("/ado92qeap9alkn")
 */
class WipeController extends AbstractController
{ 
	/**
	* @Route("/")
	*/
	public function indexAction(Session $session, HuwelijkService $huwelijkService, PersonenService $persoonService)
	{
		$huwelijk = $session->get('huwelijk');
		$user = $session->get('user');	
		
		$personen = $persoonService->getAll();		
		foreach ($personen as $persoon)
		{
			$persoonService->delete($persoon);
			
		}
		
		$huwelijken = $huwelijkService->getAll();
		foreach ($huwelijken as $huwelijk)
		{
			$huwelijkService->delete($huwelijk);
		}
		
		$this->addFlash('success', 'De database is leeggemaakt van huwelijken en personen');
		return $this->redirect($this->generateUrl('app_home_index'));
	}
}
