<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use App\Service\MessageService;
use JsonSchema\Exception\ResourceNotFoundException;
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
    public function submitrequestAction(Session $session, CommonGroundService $commonGroundService, MessageService $messageService, RequestService $requestService)
    {
        $request = $session->get('request');
        $request['status'] = 'submitted';
        unset($request['submitters']);
        unset($request['children']);
        unset($request['parent']);
        if ($request = $requestService->updateRequest($request, $request['@id'])) {
            $session->set('request', $request);
            $contact = $commonGroundService->getResource($request['submitters'][0]['person']);
            if (key_exists('emails', $contact) && key_exists(0, $contact['emails'])) {
                $messageService->createMessage($contact, ['request' => $request, 'requestType' => $commonGroundService->getResource($request['requestType'])], 'https://wrc.huwelijksplanner.online/templates/66e43592-22a2-49c2-8c3e-10d9a00d5487');
            }
            $this->addFlash('success', 'Uw verzoek is ingediend');
        } else {
            $this->addFlash('danger', 'Uw verzoek kon niet worden ingediend');
        }

        return $this->redirect($this->generateUrl('app_default_slug', ["slug" => "checklist"]));
    }

    /**
     * @Route("request/cancel")
     */
    public function cancelrequestAction(Session $session, RequestService $requestService, CommonGroundService $commonGroundService)
    {
        $request = $session->get('request');

        if ($request['status'] != "submitted") {
            if (isset($request['submitters'])) {
                foreach ($request['submitters'] as $submitter) {
                   $commonGroundService->deleteResource($submitter, $submitter['@id']);
                }
            }

            if ($request = $commonGroundService->deleteResource($request, $request['@id'])) {

                $session->set('request', $request);
                $this->addFlash('success', 'Uw verzoek is geannuleerd');
            } else {
                $this->addFlash('danger', 'Uw verzoek kon niet worden geannuleerd');
            }
        } else {
            $request['status'] = 'cancelled';

            unset($request['submitters']);
            unset($request['children']);
            unset($request['parent']);

            $request = $commonGroundService->updateResource($request, "https://vrc.huwelijksplanner.online/requests/" . $request['id']);
        }

        unset($_SESSION['request']);

        return $this->redirect($this->generateUrl('app_default_slug', ["slug" => "requests"]));
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
    public function assentAddAction(Session $session, Request $httpRequest, $property, RequestService $requestService, CommonGroundService $commonGroundService)
    {
        $requestType = $session->get('requestType');
        $request = $session->get('request');
        $user = $session->get('user');

        // First we need to make an new assent
        $assent = [];
        $assent['name'] = 'Instemming huwelijk of partnerschap';
        $assent['description'] = 'U is gevraagd of u wilt instemmen met een huwelijk of partnerschap';
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
        $request['properties'][$property][] = 'https://irc.zaakonline.nl' . $assent['@id'];
        unset($request['submitters']);
        $request = $requestService->updateRequest($request, "https://vrc.huwelijksplanner.online" . $request['@id']);

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
            return $this->redirect('https://digispoof.huwelijksplanner.online?responceUrl=' . $httpRequest->getScheme() . '://' . $httpRequest->getHttpHost() . $httpRequest->getBasePath() . $this->generateUrl('app_default_assentlogin', ["id" => $id]));
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
        $requestType = $commonGroundService->getResource($request['requestType']);
        $requestType = $requestService->checkRequestType($request, $requestType);
        $session->set('requestType', $requestType);

        // If user is not loged in
        if (!$assent['contact'] && $user) {

            $contact = [];
            $contact['givenName'] = $user['naam']['voornamen'];
            $contact['familyName'] = $user['naam']['geslachtsnaam'];

            //$contact= $contactService->createContact($contact);

            $contact = $commonGroundService->createResource($contact, 'https://cc.huwelijksplanner.online/people');

            $assent['contact'] = 'https://cc.zaakonline.nl' . $contact['@id'];
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
        if ($httpRequest->request->get('email') != null) {
            $contact['emails'][0] = ["name" => "primary", "email" => $httpRequest->request->get('email')];
        } else {
            unset($contact['emails']);
        }
        if ($httpRequest->request->get('telephone') != null) {
            $contact['telephones'][0] = ["name" => "primary", "telephone" => $httpRequest->request->get('telephone')];
        } else {
            unset($contact['telephones']);
        }
        if ($contact = $commonGroundService->updateResource($contact, $assent['contact'])) {
            $this->addFlash('success', $contact['name'] . ' is bijgewerkt');
        } else {
            $this->addFlash('danger', $contact['name'] . ' kon niet worden bijgewerkt');
        }

        return $this->redirect($this->generateUrl('app_default_slug', ["slug" => $request["currentStage"]]));
    }

    /**
     * @Route("/{slug}/unset/{value}", requirements={"value"=".+"})
     */
    public function unsetAction(Session $session, $slug, $value, ApplicationService $applicationService, RequestService $requestService, CommonGroundService $commonGroundService)
    {
        $variables = $applicationService->getVariables();

        $variables['request'] = $requestService->unsetPropertyOnSlug($variables['request'], $slug, $value);

        $variables['requestType'] = $requestService->checkRequestType($variables['request'], $variables['requestType']);
        unset($variables['request']['submitters']);
        unset($variables['request']['parent']);
        unset($variables['request']['children']);
        if ($variables['request'] = $requestService->updateRequest($variables['request'], $variables['request']['@id'])) {

            $session->set('request', $variables['request']);
            $session->set('requestType', $variables['requestType']);
            if ($slug == 'getuigen') {
                $slug = 'getuige';
            }

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
     * @Route("/request/new/{slug}")
     */
    public function newRequest(Session $session, $slug = null, $value = null, ApplicationService $applicationService, RequestService $requestService, CommonGroundService $commonGroundService, Request $request)
    {

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
//            var_dump($value);
//            die;
            $variables['request']['properties'] = array_merge($variables['request']['properties'], $value);
        } else {
            /*@todo throw error */
        }

        /*@todo dut configureerbaar maken */
        // hardcode overwrite for "gratis trouwen"
        if (is_array($variables['request']['properties'])) {
            if (array_key_exists("plechtigheid", $variables['request']['properties']) && $variables['request']['properties']["plechtigheid"] == "https://pdc.huwelijksplanner.online/offers/77f6419d-b264-4898-8229-9916d9deccee") {
                $variables['request']['properties']['locatie'] = "https://pdc.huwelijksplanner.online/offers/3a32750c-f901-4c99-adea-d211b96cbf48";
                $variables['request']['properties']['ambtenaar'] = "https://pdc.huwelijksplanner.online/offers/d5a657ff-846f-4d75-880c-abf4e9cb0c27";

            } // hardcode overwrite for "eenvoudig trouwen"
            elseif (array_key_exists("plechtigheid", $variables['request']['properties']) && $variables['request']['properties']["plechtigheid"] == "https://pdc.huwelijksplanner.online/offers/2b9ba0a9-376d-45e2-aa83-809ef07fa104") {
                $variables['request']['properties']['locatie'] = "https://pdc.huwelijksplanner.online/offers/3a32750c-f901-4c99-adea-d211b96cbf48";
                $variables['request']['properties']['ambtenaar'] = "https://pdc.huwelijksplanner.online/offers/d5a657ff-846f-4d75-880c-abf4e9cb0c27";
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
                $variables['request']['currentStage'] = $stage['next'];
            }
        }
        unset($variables['request']['submitters']);
        if (key_exists('parent', $variables['request'])) {
            unset($variables['request']['parent']);
        }
        if (key_exists('children', $variables['request'])) {
            unset($variables['request']['children']);
        }
        if ($variables['request'] = $requestService->updateRequest($variables['request'], $variables['request']['@id'])) {

            $session->set('request', $variables['request']);
            $session->set('requestType', $variables['requestType']);

            /*TODO: Dit moet een keer netter*/
            if ($stageName == 'partners') {
                $stageName = 'partner';
            }

            /*@todo translation*/
            $this->addFlash('success', ucfirst($stageName) . ' is ingesteld');

            // nog iets van uitleg
            if ($request->query->get('forceAssent')) {

                $session->set('requestType', false);
                $session->set('request', false);
                $session->set('user', false);
                $localSlug = $variables['slug'];
                if ($localSlug == 'partner')
                    $localSlug = 'partners';
                if (is_array($variables['request']['properties'][$localSlug])) {
                    $assent = end($variables['request']['properties'][$localSlug]);
                } else {
                    $assent = $variables['request']['properties'][$localSlug];
                }

                $returnUrl = $this->generateUrl('app_default_assentlogin', ["id" => str_replace('https://irc.v/assents/', '', $assent)]);

                return $this->redirect('https://digispoof.huwelijksplanner.online?responceUrl=' . $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . $returnUrl);
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
            $request["currentStage"] = $property["next"];
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
            return $this->redirect($this->generateUrl('app_default_slug', ["slug" => $slug]));
        }
    }

    /**
     * @Route("/betalen/betaal", name="app_default_payment")
     */
    public function paymentAction(Session $session, $slug = false, $resource = false, SjabloonService $sjabloonService, Request $httpRequest, CommonGroundService $commonGroundService, ApplicationService $applicationService, RequestService $requestService)
    {
        $variables = $applicationService->getVariables();
        if (!key_exists('order', $variables['request']['properties'])) {
            throw $this->createNotFoundException('There is no order defined');
        }
        $order = $commonGroundService->getResource($variables['request']['properties']['order']);
        $order['url'] = $order['@id'];
        if (key_exists('invoice', $variables['request']['properties']) && $variables['request']['properties']['invoice'] != null) {
            $invoice = $commonGroundService->getResource($variables['request']['properties']['invoice']);
            if ($invoice['dateCreated'] < $order['dateModified']) {
                $commonGroundService->deleteResource($invoice['@id']);
                unset($invoice);
            }
        }
        if (!isset($invoice)) {
            $invoice = $commonGroundService->createResource($order, 'https://bc.huwelijksplanner.online/order');
            $variables['request']['properties']['invoice'] = $invoice['@id'];
            unset($variables['request']['submitters']);
            unset($variables['request']['children']);
            unset($variables['request']['parent']);
            $variables['request'] = $requestService->updateRequest($variables['request'], $variables['request']['@id']);
        }
        return $this->redirect($invoice['paymentUrl']);

    }

    /**
     * @Route("/betalen/betaald/{id}")
     */
    public function payedAction(Session $session, $id = false, $slug = false, $resource = false, SjabloonService $sjabloonService, Request $httpRequest, CommonGroundService $commonGroundService, ApplicationService $applicationService, RequestService $requestService)
    {
        if (!$id) {
            throw new ResourceNotFoundException("There was no invoice defined");
        }
        $invoice = $commonGroundService->getResource('https://bc.huwelijksplanner.online/invoices/' . $id);
        if ($invoice['paid']) {
            $this->addFlash('success', 'Uw order is betaald!');
        } else {
            $this->addFlash('danger', 'De betaling is mislukt');
        }
        return $this->redirect($this->generateUrl('app_default_index') . '?request=' . $invoice['remark']);
    }

    /**
     * @Route("/", name="app_default_index")
     * @Route("/{slug}", name="app_default_slug")
     *
     */
    public function viewAction(Session $session, $slug = false, $resource = false, SjabloonService $sjabloonService, Request $httpRequest, CommonGroundService $commonGroundService, ApplicationService $applicationService, RequestService $requestService)
    {
        $variables = $applicationService->getVariables();

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
            $slug = $variables['request']["currentStage"];
        } elseif (!$slug) {
            /*@todo dit zou uit de standaard settings van de applicatie moeten komen*/
            $slug = "trouwen";
        }
        $variables['slug'] = $slug;
        //var_dump($variables['request']);

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
                $variables['requests'] = $commonGroundService->getResourceList('https://vrc.huwelijksplanner.online/requests', ['submitters.brp' => $variables['user']['@id'], 'order[dateCreated]' => 'desc']) ["hydra:member"];
                if (count($variables['requests']) == 0)
                    return $this->redirect($this->generateUrl('app_default_slug', ['requestType' => 'https://vtc.huwelijksplanner.online/request_types/5b10c1d6-7121-4be2-b479-7523f1b625f1']));
                break;
            case 'new-request':
                $variables['requestTypes'] = $commonGroundService->getResourceList('https://vtc.huwelijksplanner.online/request_types')["hydra:member"];
                break;
            case 'switch-organisation':
                $variables['organisations'] = $commonGroundService->getResourceList('https://wrc.huwelijksplanner.online/organizations')["hydra:member"];
                break;
            case 'switch-application':
                $variables['applications'] = $commonGroundService->getResourceList('https://wrc.huwelijksplanner.online/applications')["hydra:member"];
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
