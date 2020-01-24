<?php

// src/Service/BAG.php

namespace App\Service;

use App\Entity\Bericht;
use App\Entity\Persoon;
use Mailgun\Mailgun as Mailgun;
use MessageBird\Client as MessageBird;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class NotificationService
{
    private $params;
    private $mailer;
    private $templating;

    public function __construct(ParameterBagInterface $params, \Swift_Mailer $mailer, \Twig_Environment $templating)
    {
        $this->params = $params;
        $this->mailer = $mailer;
        $this->templating = $templating;
    }

    /*
     *
     *
     *
     */
    public function notify(Persoon $persoon, Bericht $bericht, array $variables, $type = null)
    {

        // If we don't get a type lets default to the settings
        if (!$type) {
            $type = $this->params->get('notification_default_type');
        }

        // Lets make aditional objects available
        $variables['type'] = $type;
        $variables['persoon'] = $persoon;
        $variables['Bericht'] = $bericht;

        // Lets go trough the options
        switch ($type) {
            case 'email':
                $this->sendMail($persoon, $bericht, $variables);
                break;
            case 'sms':
                $this->sendSMS($persoon, $bericht, $variables);
                break;
            case 'letter':
                throw new BadRequestHttpException('The notification type that you are trying to send "letter" is not (yet) supported, but it is on our roadmap', null, 426);
                break;
            default:
                throw new BadRequestHttpException('The notification type that you are trying to send "'.$type.'" is not (yet) supported', null, 400);
                break;
        }
    }

    private function sendMail(Persoon $persoon, Bericht $bericht, array $variables)
    {
        if (!$persoon->getEmailadres()) {
            throw new BadRequestHttpException('You are trying to send an email, but the person you are sending an email to dosn\'t have an email address', null, 400);
        }

        // Let get the Bericht values as templates

        $titel = $this->templating->createTemplate(strip_tags($bericht->getTitel()));
        $inhoud_text = $this->templating->createTemplate(strip_tags($bericht->getInhoud()));
        $inhoud_html = $this->templating->createTemplate($bericht->getInhoud());

        // Then we need to render the templates

        $titel = $titel->render($variables);
        $inhoud_text = $inhoud_text->render($variables);
        $inhoud_html = $inhoud_html->render($variables);

        $key = 'key-a0bd47fc10fe545468311b7e944eb035';
        $domain = 'mail.zaakonline.nl';
        $sender = 'Zaakonline.nl<info@zaakonline.nl>';

        $mg = Mailgun::create($key, 'https://api.eu.mailgun.net');
        // Lets then send it trough mailgun
        $mg->messages()->send($domain, [
            'from'    => $sender,
            'to'      => $persoon->getEmailadres(),
            'subject' => $titel,
            'html'    => $inhoud_html,
            'text'    => $inhoud_text,
        ]);

        /*
        // And finaly mail the whole thing
        $message = (
            new \Swift_Message($titel)
        )
        ->setFrom($this->params->get('notification_default_from_adress'))
        ->setTo($persoon->getEmailadres())
        ->setBody(
            $inhoud_html,
            'text/html'
        )
        ->addPart(
            $inhoud_text,
            'text/plain'
        );
        $this->mailer->send($message);
        */
    }

    public function sendSMS($bericht, $telefoonnummer)
    {

        //$inhoud_text = strip_tags($inhoud_text->render($variables));

        // Opstellen bericht
        $key = 'KlemtSTIvVWVQRS0QZJIF9qB0';
        $messageBird = new MessageBird($key, new \MessageBird\Common\HttpClient(MessageBird::ENDPOINT, 10, 10));

        $message = new \MessageBird\Objects\Message();
        $message->originator = 'Zaakonline';
        $message->recipients = [$telefoonnummer];
        $message->body = $bericht;

        try {
            $MessageResult = $messageBird->messages->create($message);
            //var_dump($MessageResult);
        } catch (\MessageBird\Exceptions\AuthenticateException $e) {
            // That means that your accessKey is unknown
            /*@todo iemand allermeren */
            //echo 'wrong login';
        } catch (\MessageBird\Exceptions\BalanceException $e) {
            // That means that you are out of credits, so do something about it.
            /*@todo iemand allermeren */
            //echo 'no balance';
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }
}
