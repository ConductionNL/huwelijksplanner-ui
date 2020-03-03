<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\ArrayLoader;

use App\Service\SjabloonService;
use App\Service\BRPService;
use App\Service\RequestTypeService;
use App\Service\ContactService;
use App\Service\AssentService;

use App\Service\CommonGroundService;
use App\Service\RequestService;
use App\Service\ApplicationService;

/**
 */
class DefaultController extends AbstractController
{
    /**
     * @Route("request/submit")
     */
    public function submitrequestAction(Session $session, CommonGroundService $commonGroundService)
    {
        $request = $session->get('request');
        $request['status'] = 'submitted';

        if ($request = $commonGroundService->updateResource($request, "https://vrc.huwelijksplanner.online/requests/" . $request['id'])) {
            $session->set('request', $request);

            $this->addFlash('success', 'Uw verzoek is ingediend');
        } else {
            $this->addFlash('danger', 'Uw verzoek kon niet worden ingediend');
        }

        return $this->redirect($this->generateUrl('app_default_slug', ["slug" => "checklist"]));
    }

    /**
     * @Route("request/cancel")
     */
    public function cancelrequestAction(Session $session, CommonGroundService $commonGroundService)
    {
        $request = $session->get('request');
        $request['status'] = 'cancelled';

        if ($request = $commonGroundService->updateResource($request, "https://vrc.huwelijksplanner.online/requests/" . $request['id'])) {

            $session->set('request', $request);
            $this->addFlash('success', 'Uw verzoek is geanuleerd');
        } else {
            $this->addFlash('danger', 'Uw verzoek kon niet worden geanuleerd');
        }

        return $this->redirect($this->generateUrl('app_default_slug', ["slug" => "checklist"]));
    }

    /**
     * @Route("/logout")
     */
    public function logoutAction(Session $session)
    {
        $session->set('requestType', false);
        $session->set('request', false);
        $session->set('user', false);
        $session->set('employee', false);
        $session->set('contact', false);

        $this->addFlash('danger', 'U bent uitgelogd');

        return $this->redirect($this->generateUrl('app_default_slug', ["slug" => "trouwen"]));
    }

    /**
     * @Route("/assent/add/{property}")
     */
    public function assentAddAction(Session $session, Request $httpRequest, $property, CommonGroundService $commonGroundService)
    {
        $requestType = $session->get('requestType');
        $request = $session->get('request');
        $user = $session->get('user');

        // First we need to make an new assent
        $assent = [];
        $assent['name'] = 'Instemming huwelijk of partnerschap';
        $assent['description'] = 'U is gevraagd of u wilt instemmen met een huwelijk of partnerschaps';
        //$assent['requester'] = (string) $requestType['sourceOrganization'];
        //$assent['request'] = $request['id'];

        // Hot fix
        if (!array_key_exists('@id', $request)) {
            $request['@id'] = $request['_links']['self']['href'];
        }

        $assent['request'] = 'https://vrc.huwelijksplanner.online' . $request['@id'];
        $assent['status'] = 'requested';
        $assent['requester'] = $user['burgerservicenummer'];

        //$assent= $assentService->createAssent($assent);
        $assent = $commonGroundService->createResource($assent, "https://irc.huwelijksplanner.online/assents");

        if (!array_key_exists($property, $request['properties'])) {
            $request['properties'][$property] = [];
        }
        $request['properties'][$property][] = 'http://irc.zaakonline.nl' . $assent['@id'];

        $request = $commonGroundService->updateResource($request, "https://vrc.huwelijksplanner.online" . $request['@id']);

        $session->set('requestType', false);
        $session->set('request', false);
        $session->set('user', false);

        return $this->redirect($this->generateUrl('app_default_assentlogin', ["id" => $assent["id"]]));
    }

    /**
     * @Route("/assent/{id}/{status}")
     */
    public function assentStatusAction(Session $session, Request $httpRequest, $id, $status, RequestService $requestService, RequestTypeService $requestTypeService, CommonGroundService $commonGroundService, BRPService $brpService, AssentService $assentService, ContactService $contactService, SjabloonService $sjabloonService)
    {
        $request = $session->get('request');
        $requestType = $session->get('requestType');
        $user = $session->get('user');

        $assent = $assentService->getAssent($id);
        $assent['status'] = $status;

        if ($assentService->updateAssent($assent)) {
            $this->addFlash('success', 'Uw instemmings status is bijgewerkt naar ' . $status);
        } else {
            $this->addFlash('danger', 'Uw instemmings status kon niet worden bijgewerkt');
        }

        return $this->redirect($this->generateUrl('app_default_assentlogin', ["id" => $assent["id"]]));
    }

    /**
     * @Route("/assent/{id}", requirements={"id": "^(?!set).+"})
     */
    public function assentLoginAction(Session $session, Request $httpRequest, $id, RequestService $requestService, RequestTypeService $requestTypeService, CommonGroundService $commonGroundService, BRPService $brpService, AssentService $assentService, ContactService $contactService, SjabloonService $sjabloonService)
    {
        // We might have a returning user
        $user = $session->get('user');

        // Lets first see if we have a login
        $bsn = $httpRequest->request->get('bsn');
        if (!$bsn) {
            $bsn = $httpRequest->query->get('bsn');
        }
        if (!$bsn && !$user) {
            // No login suplied so redirect to digispoof
            //return $this->redirect('http://digispoof.zaakonline.nl?responceUrl='.urlencode($httpRequest->getScheme() . '://' . $httpRequest->getHttpHost().$httpRequest->getBasePath()));
            return $this->redirect('http://digispoof.huwelijksplanner.online?responceUrl=' . $httpRequest->getScheme() . '://' . $httpRequest->getHttpHost() . $httpRequest->getBasePath() . $this->generateUrl('app_default_assentlogin', ["id" => $id]));
        }


        // if we have a login, lets do a login
        if ($bsn && $persoon = $brpService->getPersonOnBsn($bsn)) {
            $session->set('user', $persoon);
            $user = $session->get('user');


            $this->addFlash('success', 'Welkom ' . $persoon['naam']['voornamen']);
        }

        // If we do not have a user at this point we need to error
        if (!$user) {
            $this->addFlash('danger', 'U kon helaas niet worden ingelogd');
            return $this->redirect($this->generateUrl('app_default_index'));
        }

        $assent = $commonGroundService->getResource('https://irc.huwelijksplanner.online/assents/' . $id);

        $request = $commonGroundService->getResource($assent['request']);
        $session->set('request', $request);

        // Lets also set the request type
        $requestType = $commonGroundService->getResource($request['request_type']);
        $requestType = $requestService->checkRequestType($request, $requestType);
        $session->set('requestType', $requestType);

        // If user is not loged in
        if (!$assent['contact'] && $user) {

            $contact = [];
            $contact['givenName'] = $user['naam']['voornamen'];
            $contact['familyName'] = $user['naam']['geslachtsnaam'];

            //$contact= $contactService->createContact($contact);

            $contact = $commonGroundService->createResource($contact, 'https://cc.huwelijksplanner.online/people');

            $assent['contact'] = 'http://cc.zaakonline.nl' . $contact['@id'];
            $assent['person'] = $user['burgerservicenummer'];

            $assent = $assentService->updateAssent($assent);
        }

        // Let render stuff

        $products = [];
        $variables = ["requestType" => $requestType, "request" => $request, "user" => $user, "products" => $products, "assent" => $assent];

        $template = $sjabloonService->getOnSlug('assent');

        // We want to include the html in our own template
        $html = $template['content'];

        $template = $this->get('twig')->createTemplate($html);
        $template = $template->render($variables);

        return $response = new Response(
            $template,
            Response::HTTP_OK,
            ['content-type' => 'text/html']
        );

    }




    /**
     * @Route("/data")
     */
    public function dataAction(Session $session)
    {
        $request = $session->get('request');
        $user = $session->get('user');

        $response = new JsonResponse($request);
        return $response;
    }


    /**
     * @Route("/update/assent/{id}", methods={"POST"})
     */
    public function updateAssentAction(Session $session, Request $httpRequest, $id, RequestService $requestService, ContactService $contactService, AssentService $assentService, CommonGroundService $commonGroundService)
    {
        $requestType = $session->get('requestType');
        $request = $session->get('request');
        $user = $session->get('user');

        $assent = $assentService->getAssent($id);
        $contact = $commonGroundService->getResource($assent['contact']);

        if ($httpRequest->request->get('givenName')) {
            $contact['givenName'] = $httpRequest->request->get('givenName');
        }
        if ($httpRequest->request->get('familyName')) {
            $contact['familyName'] = $httpRequest->request->get('familyName');
        }

        $contact['emails'][0] = ["name" => "primary", "email" => $httpRequest->request->get('email')];
        $contact['telephones'][0] = ["name" => "primary", "telephone" => $httpRequest->request->get('telephone')];

        if ($contact = $commonGroundService->updateResource($contact, $assent['contact'])) {
            $this->addFlash('success', $contact['name'] . ' is bijgewerkt');
        } else {
            $this->addFlash('danger', $contact['name'] . ' kon niet worden bijgewerkt');
        }

        return $this->redirect($this->generateUrl('app_default_slug', ["slug" => $request["current_stage"]]));
    }

    /**
     * @Route("/{slug}/unset/{value}", requirements={"value"=".+"})
     */
    public function unsetAction(Session $session, $slug, $value, ApplicationService $applicationService, RequestService $requestService, CommonGroundService $commonGroundService)
    {
        $variables = $applicationService->getVariables();

        $variables['request'] = $requestService->unsetPropertyOnSlug($variables['request'], $slug, $value);

        $variables['requestType'] = $requestService->checkRequestType($variables['request'], $variables['requestType']);

        if ($variables['request'] = $commonGroundService->updateResource($variables['request'], 'https://vrc.huwelijksplanner.online' . $variables['request']['@id'])) {

            $session->set('request', $variables['request']);
            $session->set('requestType', $variables['requestType']);

            /*@todo translation*/
            $this->addFlash('success', ucfirst($slug) . ' geannuleerd');
            return $this->redirect($this->generateUrl('app_default_slug', ["slug" => $slug]));
        } else {
            /*@todo translation*/
            $this->addFlash('danger', ucfirst($slug) . ' kon niet worden geannuleerd');
            return $this->redirect($this->generateUrl('app_default_slug', ["slug" => $slug]));
        }

    }

    /**
     * @Route("/post", name="app_default_post_request")
     * @Route("/{slug}/post", name="app_default_post")
     * @Route("/{slug}/set/{value}" , requirements={"value"=".+"}, name="app_default_set")
     */
    public function setAction(Session $session, $slug = null, $value = null, ApplicationService $applicationService, RequestService $requestService, CommonGroundService $commonGroundService, Request $request)
    {

        $variables = $applicationService->getVariables();
        $variables['slug'] = $slug;

        // We want to be able to handle json body posts
        if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->getContent(), true);
            $request->request->replace(is_array($data) ? $data : array());
        }

        if ($request->get('_route') == "app_default_post" || $request->get('_route') == "app_default_post_request") {
            parse_str($request->getContent(), $value);
        }

        // If we have a slug then a specific property is bieng set
        if ($slug) {
            // dit mag wat abstracter
            if ($slug == "datum") {
                $date = $value["datum"];
                $time = $value["tijd"];
                $dateArray = explode(" ", $date);
                $value = date('d-m-Y H:i', strtotime("$dateArray[1] $dateArray[2] $dateArray[3] $time GMT+0100"));
            }
            $variables['request'] = $requestService->setPropertyOnSlug($variables['request'], $variables['requestType'], $slug, $value);

        } // if not the we are asuming a "broad" form that wants to update anything in the reqoust, so we merge arrays
        elseif (is_array($value)) {

            $variables['request']['properties'] = array_merge($variables['request']['properties'], $value);
        } else {
            /*@todo throw error */
        }

        /*@todo dut configureerbaar maken */
        // hardcode overwrite for "gratis trouwen"
        if (array_key_exists("plechtigheid", $variables['request']['properties']) && $variables['request']['properties']["plechtigheid"] == "https://pdc.huwelijksplanner.online/products/190c3611-010d-4b0e-a31c-60dadf4d1c62") {
            $variables['request']['properties']['locatie'] = "https://pdc.huwelijksplanner.online/products/7a3489d5-2d2c-454b-91c9-caff4fed897f";
            $variables['request']['properties']['ambtenaar'] = "https://pdc.v/products/55af09c8-361b-418a-af87-df8f8827984b";
        } // hardcode overwrite for "eenvoudig trouwen"
        elseif (array_key_exists("plechtigheid", $variables['request']['properties']) && $variables['request']['properties']["plechtigheid"] == "https://pdc.huwelijksplanner.online/products/16353702-4614-42ff-92af-7dd11c8eef9f") {
            $variables['request']['properties']['locatie'] = "https://pdc.huwelijksplanner.online/products/7a3489d5-2d2c-454b-91c9-caff4fed897f";
            $variables['request']['properties']['ambtenaar'] = "https://pdc.huwelijksplanner.online/products/55af09c8-361b-418a-af87-df8f8827984b";
        } else {
            if (key_exists('locatie', $variables['request']['properties']) && $slug == 'plechtigheid') {
                unset($variables['request']['properties']['locatie']);
                $this->addFlash('success', 'U kunt nu een locatie kiezen');
            }
            if (key_exists('ambtenaar', $variables['request']['properties']) && $slug == 'plechtigheid') {
                unset($variables['request']['properties']['ambtenaar']);
                $this->addFlash('success', 'U kunt nu een ambtenaar kiezen');
            }
        }
//echo '</pre>';
        // Lets see if we need to jump stage

        // Let see if we the current stage isn't completed by now
        $variables['requestType'] = $requestService->checkRequestType($variables['request'], $variables['requestType']);
        $stageName = $slug;

        foreach ($variables['requestType']['stages'] as $stage) {
            if ($stage['slug'] == $slug && array_key_exists('completed', $stage) && $stage['completed']) {
                $stageName = $stage['name'];
                $slug = $stage['next'];
                $variables['request']['current_stage'] = $stage['next'];
            }
        }

        if ($variables['request'] = $commonGroundService->updateResource($variables['request'], 'https://vrc.huwelijksplanner.online' . $variables['request']['@id'])) {

            $session->set('request', $variables['request']);
            $session->set('requestType', $variables['requestType']);


            /*@todo translation*/
            $this->addFlash('success', ucfirst($stageName) . ' is ingesteld');

            // nog iets van uitleg
            if ($request->query->get('forceAssent')) {

                $session->set('requestType', false);
                $session->set('request', false);
                $session->set('user', false);
                $localSlug = $variables['slug'];
                if($localSlug == 'partner')
                    $localSlug = 'partners';
                if (is_array($variables['request']['properties'][$localSlug])) {
                    $assent = end($variables['request']['properties'][$localSlug]);
                } else {
                    $assent = $variables['request']['properties'][$localSlug];
                }

                $returnUrl = $this->generateUrl('app_default_assentlogin', ["id" => str_replace('http://irc.v/assents/', '', $assent)]);

                return $this->redirect('https://digispoof.huwelijksplanner.online?responceUrl='. $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . $returnUrl);
            }

            return $this->redirect($this->generateUrl('app_default_slug', ["slug" => $slug]));
        } else {
            /*@todo translation*/
            $this->addFlash('danger', ucfirst($stageName) . ' kon niet worden ingesteld');
            return $this->redirect('', $this->generateUrl('app_default_view', ["slug" => $slug]));
        }
    }

    /**
     * @Route("/{slug}/datum")
     */
    public function datumAction(Session $session, $slug, Request $httprequest, RequestService $requestService)
    {
        $requestType = $session->get('requestType');
        $request = $session->get('request');
        $user = $session->get('user');

        // Lets get the curent property
        $arrIt = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($requestType['stages']));

        foreach ($arrIt as $sub) {
            $subArray = $arrIt->getSubIterator();
            if ($subArray['slug'] === $slug) {
                $property = iterator_to_array($subArray);
                break;
            }
        }
        /* @todo we should turn this into symfony form */
        if ($httprequest->isMethod('POST') && $httprequest->request->get('datum')) {


            $dateArray = (explode(" ", $httprequest->request->get('datum')));
            $date = strtotime($dateArray[1] . ' ' . $dateArray[2] . ' ' . $dateArray[3]);
            $postdate = date('Y-m-d', $date);
            $displaydate = date('d-m-Y', $date);


            $request['properties']['datum'] = $displaydate;
        }

        $request['properties'][$property["name"]] = $displaydate;


        if ($request = $requestService->updateRequest($request)) {
            $request["current_stage"] = $property["next"];
            $request = $requestService->updateRequest($request);
            $session->set('request', $request);

            $requestType = $requestService->checkRequestType($request, $requestType);
            $session->set('requestType', $requestType);

            // Lets find the stage that we are add
            $arrIt = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($requestType['stages']));

            foreach ($arrIt as $sub) {
                $subArray = $arrIt->getSubIterator();
                if ($subArray['slug'] === $slug) {
                    $property = iterator_to_array($subArray);
                    break;
                }
            }

            $this->addFlash('success', ucfirst($slug) . ' is ingesteld');

            if (isset($stage) && array_key_exists("completed", $stage) && $stage["completed"]) {
                $slug = $stage["next"];
            } elseif (isset($stage) && array_key_exists("slug", $stage)) {
                $slug = $stage["slug"];
            }

            return $this->redirect($this->generateUrl('app_default_slug', ["slug" => $slug]));
        } else {
            $this->addFlash('danger', ucfirst($slug) . ' kon niet worden ingesteld');
            return $this->redirect($this->generateUrl('app_default_slug', ["slug" => $slug]));;
        }
    }

    /**
     * @Route("/", name="app_default_index")
     * @Route("/{slug}", name="app_default_slug")
     *
     */
    public function viewAction(Session $session, $slug = false, $resource = false, SjabloonService $sjabloonService, Request $httpRequest, CommonGroundService $commonGroundService, ApplicationService $applicationService, RequestService $requestService)
    {
        $variables = $applicationService->getVariables();
        $variables['slug'] = $slug;
        /*
         *
            // If we dont have a user requested slug lets go to the current request stage
            if(!$slug && array_key_exists ("current_stage", $request) && $request["current_stage"] != null){
                $slug = $request["current_stage"];
            }
            elseif(!$slug && $requestType){
                $slug = $requestType['stages'][0]['slug'];
            }
            */
        //$variables['request']

        // If we have a cuurent stage on the request
        if (!$slug && array_key_exists('request', $variables)) {
            $slug = $variables['request']["current_stage"];
        } elseif (!$slug) {
            /*@todo dit zou uit de standaard settings van de applicatie moeten komen*/
            $slug = "trouwen";
        }

        /*@todo olld skool overwite variabel maken */
        switch ($slug) {
            case null :
                $slug = 'trouwen';
                break;
            case 'ambtenaar':
                $variables['products'] = $commonGroundService->getResourceList('https://pdc.huwelijksplanner.online/products', ['groups.id' => '7f4ff7ae-ed1b-45c9-9a73-3ed06a36b9cc']);
                break;
            case 'locatie':
                $variables['products'] = $commonGroundService->getResourceList('https://pdc.huwelijksplanner.online/products', ['groups.id' => '170788e7-b238-4c28-8efc-97bdada02c2e']);
                break;
            case 'plechtigheid':
                $variables['products'] = $commonGroundService->getResourceList('https://pdc.huwelijksplanner.online/products', ['groups.id' => '1cad775c-c2d0-48af-858f-a12029af24b3']);
                break;
            case 'extra':
                $variables['products'] = $commonGroundService->getResourceList('https://pdc.huwelijksplanner.online/products', ['groups.id' => 'f8298a12-91eb-46d0-b8a9-e7095f81be6f']);
                break;
            case 'requests':
                $variables['requests'] = $commonGroundService->getResourceList('https://vrc.huwelijksplanner.online/requests', ['submitter' => $variables['user']['burgerservicenummer'], 'order[date_created]' => 'desc']) ["hydra:member"];
                if (count($variables['requests']) == 0)
                    return $this->redirect($this->generateUrl('app_default_slug', ['requestType' => 'http://vtc.huwelijksplanner.online/request_types/5b10c1d6-7121-4be2-b479-7523f1b625f1']));
                break;
            case 'new-request':
                $variables['requestTypes'] = $commonGroundService->getResourceList('https://vtc.huwelijksplanner.online/request_types')["hydra:member"];
                break;
            case 'switch-organisation':
            	$variables['organisations'] = $commonGroundService->getResourceList('http://wrc.huwelijksplanner.online/organizations')["hydra:member"];
            	break;
            case 'switch-application':
            	$variables['applications'] = $commonGroundService->getResourceList('http://wrc.huwelijksplanner.online/applications')["hydra:member"];
            	break;
        }

        if ($template = $sjabloonService->getOnSlug($slug)) {
            // We want to include the html in our own template
            $html = $template['content'];

            $template = $this->get('twig')->createTemplate($html);
            $template = $template->render($variables);

            return $response = new Response(
                $template,
                Response::HTTP_OK,
                ['content-type' => 'text/html']
            );
        } else {
            throw $this->createNotFoundException('This page could not be found');
        }
    }
}
