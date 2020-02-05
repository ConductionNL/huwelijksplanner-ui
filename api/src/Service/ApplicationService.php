<?php

// src/Service/BRPService.php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\Cache\Adapter\AdapterInterface as CacheInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface; 
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

use App\Service\CommonGroundService;
use App\Service\RequestService;

class ApplicationService
{
    private $params;
    private $cache;
    private $session;
    private $flashBagInterface;
    private $request ;
    private $commonGroundService;
    private $requestService;

    public function __construct(ParameterBagInterface $params, CacheInterface $cache, SessionInterface $session, FlashBagInterface $flash, RequestStack $requestStack, CommonGroundService $commonGroundService, RequestService $requestService)
    {
        $this->params = $params;
        $this->cash = $cache;
        $this->session= $session;
        $this->flash = $flash;
        $this->request= $requestStack->getCurrentRequest();
        $this->commonGroundService = $commonGroundService;
        $this->requestService= $requestService;

    }
        
    /*
     * Get a single resource from a common ground componant
     */
    public function getVariables()
    {
    	$variables = [];
    	
    	// Lets handle the loading of a product is we have one
    	$resource= $this->request->get('resource');
    	if($resource|| $resource = $this->request->query->get('resource')){
    		/*@todo dit zou de commonground service moeten zijn */
    		$variables['resource'] = $this->commonGroundService->getResource($resource);
    	}
    	
    	// Lets handle a posible login
    	$bsn = $this->request->get('bsn');
    	if($bsn || $bsn = $this->request->query->get('bsn')){
    		$user = $this->commonGroundService->getResource('https://brp.zaakonline.nl/ingeschrevenpersonen/'.$bsn);
    		$this->session->set('user', $user);
    	}
    	$variables['user']  = $this->session->get('user');    	
    	
    	// @todo iets met organisaties en applicaties    	
    	$organization= $this->request->get('organization');
    	if($organization|| $organization= $this->request->query->get('organization')){    		
    		$this->session->set('organization', $organization);
    	}
    	else{
    		/*@todo param bag interface */
    		$this->session->set('organization', '000000');
    	}
    	$variables['organization']  = $this->session->get('organization');
    	
    	// application
    	$application= $this->request->get('application');
    	if($application|| $application= $this->request->query->get('application')){
    		$this->session->set('application', $application);
    	}
    	else{
    		/*@todo param bag interface */
    		$this->session->set('application', '0000000');
    	}
    	$variables['application']  = $this->session->get('application');
    	
    	
    	
    	// Let handle posible request creation
    	$requestType = $this->request->request->get('requestType');
    	if($requestType || $requestType=  $this->request->query->get('requestType')){
    		    		
    		$requestParent = $this->request->request->get('requestParent');
    		if(!$requestParent){ $requestParent =  $this->request->query->get('requestParent');}
    		
    		$requestType = $this->commonGroundService->getResource($requestType);
    		$request = $this->requestService->createFromRequestType($requestType, $requestParent);
    		
    		// Validate current reqoust type
    		$requestType = $this->requestService->checkRequestType($request, $requestType);
    		
    		$this->session->set('request', $request);
    		$this->session->set('requestType', $requestType);
    		
    		/* @todo translation */
    		$this->flash->add('success', 'Verzoek voor '.$requestType['name'].' opgestart');   	
    	}
    	
    	
    	// Lets handle the loading of a request
    	$request= $this->request->request->get('request');
    	if($request || $request =  $this->request->query->get('request')){
    		$request = $this->commonGroundService->getResource($request);
    		$requestType = $this->commonGroundService->getResource($request['request_type']);
    		
    		// Validate current reqoust type
    		$requestType = $this->requestService->checkRequestType($request, $requestType);
    		
    		$this->session->set('request', $request);
    		$this->session->set('requestType', $requestType);
    		
    		/* @todo translation */
    		$this->flash->add('success', 'Verzoek voor '.$requestType['name'].' ingeladen');    		
    	}
    	
    	$variables['request'] = $this->session->get('request');
    	$variables['requestType'] = $this->session->get('requestType');    	
    	
    	return $variables;
    }
    
    
}
