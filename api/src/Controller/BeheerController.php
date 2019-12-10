<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

// The services
use App\Service\ProductService;
use App\Service\HuwelijkService;
use App\Service\AmbtenaarService;
use App\Service\LocatieService;
use App\Service\SjabloonService;
use App\Service\ResourceService;
use App\Service\TrouwenService;
use App\Service\AgendaService;

// The forms
use App\Form\GroupType;
use App\Form\ProductType;
use App\Form\EmployeeType;
//use App\Form\PaginaType;
//use App\Form\BerichtType;
use App\Form\HuwelijkType;

// New Service
use App\Service\PdcService;
use App\Service\EmployeeService;
use App\Service\ContactService;
use App\Service\RequestService;
use App\Service\RequestTypeService; 
use App\Service\AssentService;



/**
 * @Route("/beheer")
 */
class BeheerController extends AbstractController
{
	/**
	 * @Route("/")
	 */
	public function indexAction(Session $session, HuwelijkService $huwelijkService)
	{
		$huwelijk = $session->get('huwelijk');
		$user = $session->get('user');
		$employeeContact = $session->get('employeeContact');
		
		return $this->render('beheer/index.html.twig', [
				'huwelijk' => $huwelijk,
		       'user' => $user,
		      'employeeContact' => $employeeContact
		]);
	}
	

	/**
	 * @Route("/pdc/groups")
	 */
	public function groupsAction(Session $session, PdcService $pdcService)
	{
	    $huwelijk = $session->get('huwelijk');
	    $user = $session->get('user');
	    $employeeContact = $session->get('employeeContact');	    
	    
	    /* @todo 002220647 zou eigenlijk dynamisch moeten zijn */
	    $groups = $pdcService->getGroups(['source_organization'=>'002220647']);
	    
	    return $this->render('beheer/groups.html.twig', [
	        'groups' => $groups,
	        'user' => $user,
	        'employeeContact' => $employeeContact
	    ]);
	}
	
	
	/**
	 * @Route("/group/{id}")
	 */
	public function groupAction(Request $request, Session $session, PdcService $pdcService, $id = null)
	{
	    $huwelijk = $session->get('huwelijk');
	    $user = $session->get('user');
	    $employeeContact = $session->get('employeeContact');	    
	    
	    
	    if($id){
	       $group =  $pdcService->getGroup($id);
	    }
	    else{
	       $group = [];
	    }
	    
	    $form = $this->createForm(GroupType::class,  $group);	    
	    $form->handleRequest($request);
	    
	    if ($form->isSubmitted() && $form->isValid())
	    {
	        $group = $form->getData();
	        unset($group['_embedded']);
	        unset($group['_links']);
	        if($group = $pdcService->updateGroup($group)){
	            $this->addFlash('success', 'Groep bijgewerkt');	            
	        }
	        else{
	            $this->addFlash('danger', 'Groep niet bijgewerkt');	            
	        }
	        
	        
	        return $this->redirectToRoute('app_beheer_group',['id'=>$id]);
	    }
	    elseif($form->isSubmitted() && !$form->isValid())
	    {	        
	        $this->addFlash('danger', 'Groep niet bijgewerkt');
	        return $this->redirectToRoute('app_beheer_group',['id'=>$id]);
	    }
	    else{
	        $form = $this->createForm(GroupType::class,$group);
	    }
	    
	    return $this->render('beheer/group.html.twig', [
	        'form' => $form->createView(),
	        'group' => $group,
	        'user' => $user,
	        'employeeContact' => $employeeContact
	    ]);
	}
	
	
	
	/**
	 * @Route("/product/{id}")
	 */
	public function productAction(Request $request, Session $session, PdcService $pdcService, $id = null)
	{
	    $huwelijk = $session->get('huwelijk');
	    $user = $session->get('user');
	    $employeeContact = $session->get('employeeContact');
	    
	    
	    if($id){
	        $product =  $pdcService->getProduct($id);
	    }
	    else{
	        $product = [];
	    }
	    
	    $form = $this->createForm(ProductType::class,  $product);
	    $form->handleRequest($request);
	    
	    if ($form->isSubmitted() && $form->isValid())
	    {
	        $product = $form->getData();
	        unset($product['_embedded']);
	        unset($product['_links']);
	        if($product = $pdcService->updateProduct($product)){
	            $this->addFlash('success', 'Product bijgewerkt');
	        }
	        else{
	            $this->addFlash('danger', 'Product niet bijgewerkt');
	        }
	        
	        
	        return $this->redirectToRoute('app_beheer_product',['id'=>$id]);
	    }
	    elseif($form->isSubmitted() && !$form->isValid())
	    {
	        $this->addFlash('danger', 'Product niet bijgewerkt');
	        return $this->redirectToRoute('app_beheer_product',['id'=>$id]);
	    }
	    else{
	        $form = $this->createForm(GroupType::class,$group);
	    }
	    
	    return $this->render('beheer/product.html.twig', [
	        'form' => $form->createView(),
	        'product' => $product,
	        'user' => $user,
	        'employeeContact' => $employeeContact
	    ]);
	}
	
	/**
	 * @Route("/requests")
	 */
	public function requestsAction(Session $session, RequestService $requestService, RequestTypeService $requestTypeService)
	{
	    $huwelijk = $session->get('huwelijk');
	    $user = $session->get('user');
	    $employeeContact = $session->get('employeeContact');
	    
	    /* @todo 002220647 zou eigenlijk dynamisch moeten zijn */
	    $requests = $requestService->getRequests(['source_organization'=>'002220647']);
	    
	    foreach($requests as $key => $value){
	    	$requests[$key]['request_typeObject'] = $requestTypeService->getRequestTypeOnUri($value['request_type']);
	    }
	    
	    return $this->render('beheer/requests.html.twig', [
	        'requests' => $requests,
	        'user' => $user,
	        'employeeContact' => $employeeContact
	    ]);
	}
	
	
	
	/**
	 * @Route("/request/{id}")
	 */
	public function requestAction(Session $session, RequestService $requestService, AssentService $assentService, $id = null)
	{
	    $huwelijk = $session->get('huwelijk');
	    $user = $session->get('user');
	    $employeeContact = $session->get('employeeContact');
	    
	    // What if we already have an official?
	    $request = [];
	    $locatie= null;
	    $ambtenaar= null;
	    $ceremonie= null;
	    $getuigen = [];
	    $partners= [];
	    
	    if($id){
	    	$request =  $requestService->getRequestOnId($id);
	        
	        if( array_key_exists('partner1', $request['properties']) && $request['properties']['partner1']){
	        	$partners[] = $assentService->getAssentOnUri($request['properties']['partner1']);
	        }
	        if( array_key_exists('partner2', $request['properties']) && $request['properties']['partner2']){
	        	$partners[] = $assentService->getAssentOnUri($request['properties']['partner2']);
	        }
	        
	        $getuigen = [];
	        if(array_key_exists('getuigenPartner1', $request['properties'])){
	        	$getuigen = array_merge($getuigen, $request['properties']['getuigenPartner1']);
	        }
	        if(array_key_exists('getuigenPartner2', $request['properties'])){
	        	$getuigen = array_merge($getuigen, $request['properties']['getuigenPartner2']);
	        }
	        
	        foreach ($getuigen as $key => $value){
	        	$getuigen[$key] = $assentService->getAssentOnUri($value);
	        }	        
	    }
	    
	    // Form
	    $form = $this->createForm(HuwelijkType::class,  $request);
	    $form->handleRequest($request);
	    
	    return $this->render('beheer/request.html.twig', [
	    		'form' => $form->createView(),
	    		'verzoek' => $request,
	    		'user' => $user,
	    		'partners' => $partners,
	    		'getuigen' => $getuigen,
	    		'ceremonie' => $ceremonie,
	    		'locatie' => $locatie,
	    		'ambtenaar' => $ambtenaar
	    ]);
	}
	
	/**
	 * @Route("/employees")
	 */
	public function employeesAction(Session $session, EmployeeService $employeeService, ContactService $contactService)
	{
	    $huwelijk = $session->get('huwelijk');
	    $user = $session->get('user');
	    $employeeContact = $session->get('employeeContact');
	    
	    /* @todo 002220647 zou eigenlijk dynamisch moeten zijn */
	    $employees = $employeeService->getEmployees(['source_organization'=>'002220647']);
	    
	    foreach($employees as $key => $value){
	        $employees[$key]['contactObject'] = $contactService->getContactOnUri($value['contact']);
	    }
	    
	    return $this->render('beheer/employees.html.twig', [
	        'employees' => $employees,
	        'user' => $user,
	        'employeeContact' => $employeeContact
	    ]);
	}
	
	/**
	 * @Route("/employee/{id}")
	 */
	public function employeeAction(Session $session, EmployeeService $employeeService, ContactService $contactService, $id = null)
	{
	    $huwelijk = $session->get('huwelijk');
	    $user = $session->get('user');
	    $employeeContact = $session->get('employeeContact');
	    $employee = [];
	    
	    if($id){
	        $employee =  $employeeService->getEmployee($id);
	        $employee['contactObject'] = $contactService->getContactOnUri($employee['contact']);
	        $employee['given_name'] = $employee['contactObject']['given_name'];
	        $employee['additional_name'] = $employee['contactObject']['additional_name'];
	        $employee['family_name'] = $employee['contactObject']['family_name'];
	    }
	    
	    $form = $this->createForm(EmployeeType::class,  $employee);
	    //$form->handleRequest($request);
	    
	    return $this->render('beheer/employee.html.twig', [
	         'form' => $form->createView(),
	        'employee' => $employee,
	        'user' => $user,
	        'employeeContact' => $employeeContact
	    ]);
	}
}
