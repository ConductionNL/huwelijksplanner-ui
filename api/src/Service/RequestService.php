<?php

// src/Service/BRPService.php

namespace App\Service;

use GuzzleHttp\Client;
use http\Message;
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
    private $messageService;

    public function __construct(ParameterBagInterface $params, CacheInterface $cache, SessionInterface $session, CommonGroundService $commonGroundService, MessageService $messageService)
    {
        $this->params = $params;
        $this->cash = $cache;
        $this->session= $session;
        $this->commonGroundService = $commonGroundService;
        $this->messageService = $messageService;

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
        $request['requestType'] = $requestType['@id'];
        $request['organization'] = $organization['@id'];
        $request['application'] = $application;
        //$request['organization'] = $organization;
        $request['status']='incomplete';
        $request['properties']= [];

        $requestTypeObject = $this->commonGroundService->getResource($request['requestType']);
//    	if($requestTypeObject['unique'] == true)
//        {
//            $existingRequests = $this->commonGroundService->getResourceList('http://vrc.huwelijksplanner.online/requests', ['request_type'=>$request['request_type'], 'status[]'=>['incomplete','processed','submitted'], 'submitter'=>$this->session->get('bsn')]);
//            if(count($existingRequests) > 0)
//            {
//                //TODO: Throw error
//               throw new
//               die;
//            }
//        }
    	if($user){
    		$request['submitter'] = $user['burgerservicenummer'];
    		//$request['submitters'] = [$user['burgerservicenummer']];
    	}

    	// juiste startpagina weergeven
    	if(!array_key_exists ("currentStage", $request) && array_key_exists (0, $requestType['stages'])){
    		$request["currentStage"] = $requestType['stages'][0]['slug'];
    	}

    	$request = $this->commonGroundService->createResource($request, 'https://vrc.huwelijksplanner.online/requests');


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
    	$contact= $this->commonGroundService->createResource($contact, 'https://cc.huwelijksplanner.online/people');

    	$assent = [];
    	$assent['name'] = 'Instemming huwelijk partnerschp';
    	$assent['description'] = 'U bent automatisch toegevoegd aan een  huwelijk/partnerschap omdat u deze zelf heeft aangevraagd';
    	$assent['contact'] = $contact['@id'];
    	$assent['requester'] = $organization['@id'];
    	$assent['person'] = $user['burgerservicenummer'];
    	$assent['request'] = $request['@id'];
    	$assent['status'] = 'granted';
    	$assent = $this->commonGroundService->createResource($assent, 'https://irc.huwelijksplanner.online/assents');

    	$request['properties']['partners'][] = $assent['@id'];
    	$request = $this->commonGroundService->updateResource($request, $request['@id']);

    	return $request;
    }


    public function unsetPropertyOnSlug($request, $property, $value = null)
    {
        if($property == "getuige"){
            $property = "getuigen";
        }
    	// Lets see if the property exists
    	if(!array_key_exists ($property, $request['properties'])){
    		return $request;
    	}

    	// If the propery is an array then we only want to delete the givven value
    	if(is_array($request['properties'][$property])){

    		$key = array_search($value, $request['properties'][$property]);
    		$deletedValue = $request['properties'][$property][$key];
    		unset ($request['properties'][$property][$key]);

    		// If the array is now empty we want to drop the property
    		if(count($request['properties'][$property]) == 0){
    			unset ($request['properties'][$property]);
    		}
    	}

    	// If else we just drop the property
    	else{
    	    $deletedValue = $request['properties'][$property];
    		unset ($request['properties'][$property]);
    	}
    	if(key_exists('order',$request['properties'])){
    	    $order = $this->commonGroundService->getResource($request['properties']['order']);
    	    foreach($order['items'] as $item){
    	        if($item['offer'] = $deletedValue){
    	            var_dump($item);
    	            $this->commonGroundService->deleteResource($item['@id']);
                }
            }
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
                        if($contact['telephones'][0]['telephone'] == null)
                        {
                            unset($contact['telephones']);
                        }

	    				if(!empty($contact))
	    				    $contact = $this->commonGroundService->createResource($contact, 'https://cc.huwelijksplanner.online/people');

	    				unset($value['givenName']);
	    				unset($value['familyName']);
	    				unset($value['email']);
	    				unset($value['telephone']);

	    				if($value == null)
	    				    $value = [];
	    				$value['name'] = 'Instemming als '.$slug.' bij '.$requestType["name"];
	    				$value['description'] = 'U bent uitgenodigd als '.$slug.' voor het '.$requestType["name"].' van A en B'; //@TODO: hier mogen A en B nog wel namen worden. De zin is voor een partner trouwens best krom
                        if($slug=="getuige" && array_key_exists('partner', $value)){
                            $value['requester'] = $value['partner'];
                        }
                        else{
                            $value['requester'] = $requestType['source_organization']; //@TODO: ook hier een BRP-verwijzing naar de aanvragende partner
                        }
                        $value['request'] = $request['id'];
	    				$value['status'] = 'requested';
	    				if(!empty($contact))
	    				    $value['contact'] = $contact['@id'];
	    				$value = $this->commonGroundService->createResource($value, 'https://irc.huwelijksplanner.online/assents');
                        $template = 'https://wrc.huwelijksplanner.online/templates/e04defee-0bb3-4e5c-b21d-d6deb76bd1bc';
	    				$this->messageService->createMessage($contact, $value, $template);
	    			}
	    			else{
	    				//$value = $this->commonGroundService->updateResource($value, $value['@id']);
	    			}
	    			$value = $value['@id'];
	    			break;
                case 'pdc/product'; //to be deleted once this is correct
                case 'pdc/offer':
                    // var_dump($value);
                    // var_dump($request);

                    // die;

                    if(!key_exists('order', $request['properties'])){
                        $order = [];
                        $order['name'] = "Huwelijksplanner order";
                        $order['targetOrganization'] = '002220647';
                        $order['customer'] = $this->commonGroundService->getResource($request['properties']['partners'][0])['contact'];
                        // $order['customer'] = $contact;
                        $order['stage'] = 'cart'; // Deze zou leeg moeten mogen zijn
                        // $order['items'] = [];
                        // $order['customer'] = $contact['@id'];

                        if (!in_array('description',$order) || !$order['description']) {
                            $order['description'] = "Huwelijksplanner Order";
                        }

                        $order = $this->commonGroundService->createResource($order, "https://orc.huwelijksplanner.online/orders");

                        $request['properties']['order'] = $order['@id'];
                        // var_dump($order);
                    }
                    $offer = $this->commonGroundService->getResource($value);
                    // var_dump($offer);
                    // die;
                    if(!isset($order)){
                        $orderId = $order = $request['properties']['order'];

                    }
                    else{
                        $orderId = $order['@id'];
                    }
                    $orderItem = [];
                    $orderItem['offer'] = $offer['@id'];
                    $orderItem['name'] = $offer['name'];
                    $orderItem['description'] = $offer['description'];
                    $orderItem['quantity'] = 1;
                    $orderItem['price'] = number_format($offer['price'] / 100, 2, '.', ' '); // hier gaat iets mis dat dit nodig is
                    $orderItem['priceCurrency'] = $offer['priceCurrency'];
                    //$orderItem['taxPercentage'] = $offer['taxes'][0]['percentage']; // Taxes in orders en invoices moet worden bijgewerkt
                    $orderItem['taxPercentage'] = 0; /*@todo dit moet dus nog worden gefixed */
                    $orderItem['order'] = $orderId;

                    $orderItem = $this->commonGroundService->createResource($orderItem, 'https://orc.huwelijksplanner.online/order_items');
                    // $request['properties']['order']['items'] .= $orderItem;

                    // var_dump($orderItem);
                    // die;
                    break;
	    			/*
	    		case 'cc/people':
	    			// This is a new assent so we also need to create a contact
	    			if(!array_key_exists ('@id', $value)) {
	    				$value= $this->commonGroundService->createResource($value, );
	    			}
	    			else{
	    				$value= $this->commonGroundService->updateResource($value, $value['@id']);
	    			}
	    			$value ='http://cc.huwelijksplanner.online'.$value['@id'];
	    			break;
	    		case 'pdc/product':
	    			// This is a new assent so we also need to create a contact
	    			if(!array_key_exists ('@id', $value)) {
	    				$value= $this->commonGroundService->createResource($value, 'https://pdc.huwelijksplanner.online/product');
	    			}
	    			else{
	    				$value= $this->commonGroundService->updateResource($value, $value['@id']);
	    			}
	    			$value = $value['@id'];
	    			break;
	    		case 'vrc/request':
	    			break;
	    		case 'orc/order':
	    			// This is a new assent so we also need to create a contact
	    			if(!$value['@id']){
	    				$value= $this->commonGroundService->createResource($value, 'https://orc.huwelijksplanner.online/order');
	    			}
	    			else{
	    				$value= $this->commonGroundService->updateResource($value, $value['@id']);
	    			}
	    			$value = 'http://orc.huwelijksplanner.online'.$value['@id'];
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
        //echo "<pre>";
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
            if (key_exists('properties', $request) && array_key_exists($stage['name'], $request['properties']) && $request['properties'][$stage['name']] != null) {

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
//                var_dump($requestType['stages'][$key]);
   //             var_dump($requestType['stages'][$key]);
//                var_dump($requestType['stages'][$key]['completed']);
                //var_dump($property["type"]);
                //var_dump($property["min_items"]);
                //var_dump($request["properties"]);
                //var_dump($requestType["stages"][$key]);
            }
            else{
                $requestType['stages'][$key]['completed'] = false;
            }
        }
        //var_dump($requestType["stages"]);

        return $requestType;
    }
}
