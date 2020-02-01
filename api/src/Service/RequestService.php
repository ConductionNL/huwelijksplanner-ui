<?php

// src/Service/BRPService.php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\Cache\Adapter\AdapterInterface as CacheInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use App\Service\CommonGroundService;

class RequestService
{
    private $params;
    private $cache;
    private $client;
    private $session;
    private $commonGroundService;

    public function __construct(ParameterBagInterface $params, CacheInterface $cache, SessionInterface $session, CommonGroundService $commonGroundService)
    {
        $this->params = $params;
        $this->cash = $cache;
        $this->session= $session;
        $this->commonGroundService = $commonGroundService;

    }
    
    /*
     * Creates a new reqousted basted on a reqoust type
     */
    public function createFromRequestType($requestType, $requestParent = null ,$user = null, $organization= null, $application= null)
    {
    	// If a user has not been provided let try to get one from the session
    	if(!$user){
    		$user = $this->session->get('user');
    	}
    	// If a user has not been provided let try to get one from the session
    	if(!$organization){
    		$organization = $this->session->get('organization');
    	}
    	// If a user has not been provided let try to get one from the session
    	if(!$application){
    		$application= $this->session->get('application');
    	}
    		    	
    	$request= [];
    	$request['request_type'] = $requestType;
    	$request['target_organization'] = $organization;
    	$request['application'] = $application;
    	$request['status']='incomplete';
    	$request['properties']= [];
    	if($user){    		
    		$request['submitter'] = $user['burgerservicenummer'];
    		//$request['submitters'] = [$user['burgerservicenummer']];
    	}
    	$request = $this->commonGroundService->createResource($request, 'https://vrc.zaakonline.nl/requests');    	
    	
    	
    	// There is an optional case that a request type is a child of an already exsisting one
    	if($requestParent){
    		$requestParent = $this->commonGroundService->getResource($requestParent);
    		$request['parent'] = $requestParent['@id'];
    		
    		// Lets transfer any properties that are both inthe parent and the child request
    		foreach($requestType['properties'] as $property){
    			if(array_key_exists($property['slug'], $requestParent['properties'])){
    				$request['properties'][] = $requestParent['properties'][$property['slug']];
    			}
    		}
    	}
    	    	
    	$contact = [];
    	$contact['givenName']= $user['naam']['voornamen'];
    	$contact['familyName']= $user['naam']['geslachtsnaam'];
    	$contact= $this->commonGroundService->createResource($contact, 'https://cc.zaakonline.nl/people');
    	
    	$assent = [];
    	$assent['name'] = 'Instemming huwelijk partnerschp';
    	$assent['description'] = 'U bent automatisch toegevoegd aan een  huwelijk/partnerschap omdat u deze zelf heeft aangevraagd';
    	$assent['contact'] = 'http://cc.zaakonline.nl'.$contact['@id'];
    	$assent['requester'] = $organization;
    	$assent['person'] = $user['burgerservicenummer'];
    	$assent['request'] = 'http://vrc.zaakonline.nl'.$request['@id'];
    	$assent['status'] = 'granted';
    	$assent = $this->commonGroundService->createResource($assent, 'https://irc.zaakonline.nl/assents');
    	
    	$request['properties']['partners'][] = 'http://irc.zaakonline.nl'.$assent['@id'];
    	$request = $this->commonGroundService->updateResource($request, 'https://vrc.zaakonline.nl'.$request['@id']);
    	
    	return $request;    	
    }
    
    
    public function setProperty($request, $requestType, $property, $value)
    {
    	// Lets get the curent property
    	$arrIt = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($requestType['properties']));
    	$typeProperty = false;
    	
    	// Let get the validation rules for this property
    	foreach ($arrIt as $sub) {
    		$subArray = $arrIt->getSubIterator();
    		if (array_key_exists ('name', $subArray) && $subArray['name'] === $property) {
    			$typeProperty = iterator_to_array($subArray);
    			break;
    		}
    	}
    	
    	// If this porperty doesn't exsist for this reqoust type we have an issue
    	if(!$typeProperty){
    		return false;
    	}
    	    	
    	// Let procces the value
    	switch ($typeProperty['type']) {
    		case 'array':
    			// Lets make sure that the value is an array
    			if(!is_array($request['properties'][$property['name']])){
    				$request['properties'][$property['name']] = [];
    			}    			
    			$request['properties'][$property['name']][] = $value;
    			break;
    		default:
    			$request['properties'][$property['name']] = $value;
    	}
    	    	
    	return $request;
    }
    
    public function checkRequestType($request, $requestType)
    {
        foreach ($requestType['stages'] as $key=>$stage) {

            // Overwrites for omzetten
            if (
                    (
                    $stage['name'] == 'getuigen' ||
                    $stage['name'] == 'ambtenaar' ||
                    $stage['name'] == 'locatie' ||
                    $stage['name'] == 'extras' ||
                    $stage['name'] == 'plechtigheid' ||
                    $stage['name'] == 'melding')
                    &&
                    array_key_exists('type', $request['properties'])
                    &&
                    $request['properties']['type'] == 'omzetten'
                ) {
                $requestType['stages'][$key]['completed'] = true;
            }
            if (
                    (
                            $stage['name'] == 'getuigen' ||
                            $stage['name'] == 'ambtenaar' ||
                            $stage['name'] == 'locatie' ||
                            $stage['name'] == 'extras' ||
                            $stage['name'] == 'plechtigheid' ||
                            $stage['name'] == 'melding')
                    &&
                    array_key_exists('type', $request['properties'])
                    &&
                    $request['properties']['type'] != 'omzetten'
                    ) {
                $requestType['stages'][$key]['completed'] = false;
            }

            // Lets see is we have a value for this stage in our request and has a value
            if (array_key_exists($stage['name'], $request['properties']) && $request['properties'][$stage['name']] != null) {

                // Let get the validation rules from the request type
                $arrIt = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($requestType['properties']));

                foreach ($arrIt as $sub) {
                    $subArray = $arrIt->getSubIterator();

                    if (array_key_exists('name', $subArray) and $subArray['name'] === $stage['name']) {
                        $property = iterator_to_array($subArray);
                        break;
                    }
                }
                // Als we een waarde hebben en het hoefd geen array te zijn
                if ($property['type'] != 'array') {
                    $requestType['stages'][$key]['completed'] = true;
                }
                // als het een array is zonder minimum waarden
                elseif (!array_key_exists('min_items', $property)) {
                    $requestType['stages'][$key]['completed'] = true;
                }
                // als de array een minimum waarde heeft en die waarde wordt gehaald
                elseif (array_key_exists('min_items', $property) && $property['min_items'] && count($request['properties'][$stage['name']]) >= (int) $property['min_items']) {
                    $requestType['stages'][$key]['completed'] = true;
                } else {
                    $requestType['stages'][$key]['completed'] = false;
                }

                //var_dump($key);
                //var_dump($property["type"]);
                //var_dump($property["min_items"]);
                //var_dump($request["properties"]);
                //var_dump($requestType["stages"][$key]);
            }
        }
        //var_dump($requestType["stages"]);
        //die;

        return $requestType;
    }
}
