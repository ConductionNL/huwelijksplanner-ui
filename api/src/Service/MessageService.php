<?php


namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\Cache\Adapter\AdapterInterface as CacheInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class MessageService
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

    public function createMessage($contact, $assent, $template){
        $message = [];
        $message['sender'] = '';
        $message['receiver'] = $contact['@id'];
        $message['content'] = $template;
        $message['data']['assent'] = $assent;
        $message['data']['contact'] = $contact;
        $message['status'] = 'queued';
        $message['service'] = '';

        $message = $this->commonGroundService->createResource($message, 'https://bs.huwelijksplanner.online/messages');
        return $message;
    }
}
