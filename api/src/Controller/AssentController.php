<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Service\AssentService;
use App\Service\BRPService; 
/**
 * @Route("/assent")
 */
class AssentController extends AbstractController
{ 
	/**
	* @Route("/")
	*/
	public function indexAction(Session $session)
	{
		$huwelijk = $session->get('huwelijk');
		$user = $session->get('user');
		
		return $this->render('home/index.html.twig', [
				'user' => $user,
				'huwelijk' => $huwelijk,
		]);
	}
	
	/**
	 * @Route("/{token}/reject")
	 */
	public function rejectAction(Session $session, AssentService $assentService, $token)
	{
		$person = $session->get('person');
		$assent = $assentService->getAssent($token);
		
		if($person){
			$assent = $assentService->getAssent($token);
			$assent['status'] = 'rejected';
			$assent['person'] = $person['burgerservicenummer'];
			$assent = $assentService->updateAssent($assent);
			$this->addFlash('succes', 'U instemming is bijgewerkt naar:'.$assent['status']);		
		}
		else{			
			// do error
			$this->addFlash('danger', 'U kon helaas niet worden ingelogd');		
		}
		
		return $this->render('assent/processed.html.twig', [
				'person' => $person,
				'assent' => $assent,
		]);
	}
	
	/**
	 * @Route("/{token}/confirm")
	 */
	public function confirmAction(Session $session, AssentService $assentService, $token)
	{
		$person = $session->get('person');
		$assent = $assentService->getAssent($token);
		
		if($person){
			$assent = $assentService->getAssent($token);
			$assent['status'] = 'confirmed';
			$assent['person'] = $person['burgerservicenummer'];
			$assent = $assentService->updateAssent($assent);
			$this->addFlash('succes', 'U instemming is bijgewerkt naar:'.$assent['status']);
		}
		else{
			// do error
			$this->addFlash('danger', 'U kon helaas niet worden ingelogd');		
		}
		
		return $this->render('assent/processed.html.twig', [
				'person' => $person,
				'assent' => $assent,
		]);
	}	
	
	/**
	 * @Route("/{token}/processed")
	 */
	public function processedAction(Session $session, AssentService $assentService, $token)
	{
		$assent = $assentService->getAssent($token);
		
		return $this->render('home/index.html.twig', [
				'user' => $user,
				'huwelijk' => $huwelijk,
		]);
	}
	
	/**
	 * @Route("/{token}")
	 */
	public function assentAction(Session $session, AssentService $assentService, BRPService $brpService, $token, Request $httpRequest)
	{
		$session->remove('request');
		$session->remove('user');
		$session->remove('person');
		
		$assent = $assentService->getAssent($token);
		
		$bsn = $httpRequest->request->get('bsn');
		if(!$bsn){
			$bsn =  $httpRequest->query->get('bsn');
		}
		
		if(!$bsn){			
			return $this->redirect('http://digispoof.zaakonline.nl?responceUrl=http://ta.zaakonline.nl/assent/'.$token);
		}
		
		if($person= $brpService->getPersonOnBsn($bsn)){
			$session->set('person',$person);
		}	
		else{
			// do error
		}
	    
	    return $this->render('assent/index.html.twig', [
	    	'person' => $person,
	    	'assent' => $assent,
	    ]);
	}
}
