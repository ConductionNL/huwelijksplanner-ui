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
    	$request['request_type'] = 'https://vtc.zaakonline.nl'.$requestType['@id'];
    	$request['target_organization'] = $organization;
    	$request['application'] = $application;
    	$request['status']='incomplete';
    	$request['properties']= [];

    	if($user){
    		$request['submitter'] = $user['burgerservicenummer'];
    		//$request['submitters'] = [$user['burgerservicenummer']];
    	}

    	// juiste startpagina weergeven
    	if(!array_key_exists ("current_stage", $request) && array_key_exists (0, $requestType['stages'])){
    		$request["current_stage"] = $requestType['stages'][0]['slug'];
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


    public function unsetPropertyOnSlug($request, $property, $value = null)
    {
    	// Lets see if the property exists
    	if(!array_key_exists ($property, $request['properties'])){
    		return false;
    	}

    	// If the propery is an array then we only want to delete the givven value
    	if(is_array($request['properties'][$property])){

    		$key = array_search($value, $request['properties'][$property]);
    		unset ($request['properties'][$property][$key]);

    		// If the array is now empty we want to drop the property
    		if(count($request['properties'][$property]) == 0){
    			unset ($request['properties'][$property]);
    		}
    	}
    	// If else we just drop the property
    	else{
    		unset ($request['properties'][$property]);
    	}

    	return $request;

    }

    public function setPropertyOnSlug($request, $requestType, $slug, $value)
    {
    	// Lets get the curent property
    	$typeProperty = false;

    	foreach ($requestType['properties'] as $property){
    		if($property['slug'] == $slug){
    			$typeProperty= $property;
    			var_dump($typeProperty['name']);
    			break;
    		}
    	}

    	// If this porperty doesn't exsist for this reqoust type we have an issue
    	if(!$typeProperty){
    		return false;
    	}

    	// Let see if we need to do something special
    	if(array_key_exists ('iri',$typeProperty)){
	    	switch ($typeProperty['iri']) {
	    		case 'irc/assent':

	    			// This is a new assent so we also need to create a contact
	    			if($value == null || !array_key_exists ('@id', $value)) {

	    				$contact = [];
	    				if($value != null && array_key_exists('givenName',$value)){ $contact['givenName']= $value['givenName'];}
	    				if($value != null && array_key_exists('familyName',$value)){ $contact['familyName']= $value['familyName'];}
	    				if($value != null && array_key_exists('email',$value)){
		    				$contact['emails']=[];
		    				$contact['emails'][]=["name"=>"primary","email"=> $value['email']];
	    				}
	    				if($value != null && array_key_exists('telephone',$value)){
		    				$contact['telephones']=[];
		    				$contact['telephones'][]=["name"=>"primary","telephone"=> $value['telephone']];
	    				}
	    				//var_dump($contact);
	    				if(!empty($contact))
	    				    $contact = $this->commonGroundService->createResource($contact, 'https://cc.zaakonline.nl/people');

	    				unset($value['givenName']);
	    				unset($value['familyName']);
	    				unset($value['email']);
	    				unset($value['telephone']);

	    				if($value == null)
	    				    $value = [];
	    				$value['name'] = 'Instemming als '.$slug.' bij '.$requestType["name"];
	    				$value['description'] = 'U bent uitgenodigd als '.$slug.' voor het '.$requestType["name"].' van A en B';
	    				$value['requester'] = $requestType['source_organization'];
	    				$value['request'] = 'https://vrc.zaakonline.nl/requests/'.$request['id'];
	    				$value['status'] = 'requested';
	    				if(!empty($contact))
	    				    $value['contact'] = 'http://cc.zaakonline.nl'.$contact['@id'];
	    				$value = $this->commonGroundService->createResource($value, 'https://irc.zaakonline.nl/assents');
	    			}
	    			else{
	    				//$value = $this->commonGroundService->updateResource($value, 'https://irc.zaakonline.nl/'.$value['@id']);
	    			}
	    			$value = 'http://irc.zaakonline.nl'.$value['@id'];
	    			break;
	    			/*
	    		case 'cc/people':
	    			// This is a new assent so we also need to create a contact
	    			if(!array_key_exists ('@id', $value)) {
	    				$value= $this->commonGroundService->createResource($value, 'https://cc.zaakonline.nl/people');
	    			}
	    			else{
	    				$value= $this->commonGroundService->updateResource($value, 'https://cc.zaakonline.nl/'.$value['@id']);
	    			}
	    			$value ='http://cc.zaakonline.nl'.$value['@id'];
	    			break;
	    		case 'pdc/product':
	    			// This is a new assent so we also need to create a contact
	    			if(!array_key_exists ('@id', $value)) {
	    				$value= $this->commonGroundService->createResource($value, 'https://pdc.zaakonline.nl/product');
	    			}
	    			else{
	    				$value= $this->commonGroundService->updateResource($value, 'https://pdc.zaakonline.nl/'.$value['@id']);
	    			}
	    			$value = $value['@id'];
	    			break;
	    		case 'vrc/request':
	    			break;
	    		case 'orc/order':
	    			// This is a new assent so we also need to create a contact
	    			if(!$value['@id']){
	    				$value= $this->commonGroundService->createResource($value, 'https://orc.zaakonline.nl/order');
	    			}
	    			else{
	    				$value= $this->commonGroundService->updateResource($value, 'https://orc.zaakonline.nl/'.$value['@id']);
	    			}
	    			$value = 'http://orc.zaakonline.nl'.$value['@id'];
	    			break;
	    			*/
	    	}
    	}

    	// Let validate the value
	    if(array_key_exists ('format',$typeProperty)){
	    	switch ($typeProperty['format']) {
	    		case 'array':
	    			break;
	    		case 'array':
	    			break;
	    		case 'array':
	    			break;
	    		default:
	    		//	$request['properties'][$typeProperty['name']] = $value;
	    	}
    	}

    	// Let procces the value
    	if($typeProperty['type'] == "array"){
    		// Lets make sure that the value is an array
    		if(!array_key_exists($typeProperty['name'],$request['properties']) || !is_array($request['properties'][$typeProperty['name']])){
    			$request['properties'][$typeProperty['name']] = [];
    		}
    		// If the post is also an array then lets merge the two together
    		if(is_array($value)){
    			$request['properties'][$typeProperty['name']] = array_merge($request['properties'][$typeProperty['name']], $value);
    		}
    		else{
    			$request['properties'][$typeProperty['name']][] = $value;
    		}
    	}
    	else{
    		$request['properties'][$typeProperty['name']] = $value;
    	}

    	/*@todo this misses busnes logic  */

    	// Lets update the stage
    	//$request["current_stage"] = $typeProperty["next"];

    	/*
    	 *
			if(isset($property) && array_key_exists("completed", $property) && $property["completed"]){
				$slug = $property["next"];
			}
			else{
				$slug = $property["slug"];
			}
    	 */

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
