<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Service\HuwelijkService;
use App\Service\BRPService; 
use App\Service\RequestService;
use App\Service\EmployeeService;
use App\Service\ContactService; 

/**
 * @Route("/employee")
 */
class EmployeeController extends AbstractController
{ 	
	
	/**
	* @Route("/")
	*/
	public function indexAction(Session $session)
	{
		$request= $session->get('request');
		$user = $session->get('user');
		$person = $session->get('person');
		
		//var_dump($huwelijk);
		
		return $this->render('home/index.html.twig', [
				'request' => $request,
				'user' => $user,
		]);
	}
	
	/**
	 * @Route("/login")
	 */
	public function loginAction(Session $session, Request $request, EmployeeService $employeeService, ContactService $contactService)
	{
		$id = $request->request->get('ssoid');
		if(!$id){
		    $id =  $request->query->get('ssoid');
		}
		
		if($employee= $employeeService->getEmployee($id)){
			//var_dump($persoon);
		    $session->set('employee', $employee);	
		    $employeeContact= $contactService->getContactOnUri($employee['contact']);
		    $session->set('employeeContact', $employeeContact);
		    $this->addFlash('success', 'Welkom '.$employeeContact['given_name'].''.$employeeContact['additional_name'].' '.$employeeContact['family_name']);
		}
		else{
			$this->addFlash('danger', 'U kon helaas niet worden ingelogd');		
		}
				
		$response = $this->forward('App\Controller\BeheerController::indexAction');		
		return $response;
	}
	
}
