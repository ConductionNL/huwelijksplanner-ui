<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


use App\Service\ProductService;
use App\Service\SjabloonService;
/**
 * @Route("/paginas")
 */
class PagesController extends AbstractController
{
	
	/**
	 * @Route("/{slug}")
	 */
	public function slugAction(Session $session, SjabloonService $sjabloonService, $slug, \Twig_Environment $templating)
	{
		$huwelijk = $session->get('huwelijk');
		$user = $session->get('user');
		
		$variables = ["huwelijk"=>$huwelijk,"user"=>$user];		
		$paginas = $sjabloonService->getSlug($slug);
		
		if($paginas[0]){
			// We want to include the html in our own template
			$html = "{% extends 'base.html.twig' %}{% block body %}".$paginas[0]['inhoud']."{% endblock %}";
			
			$template = $this->get('twig')->createTemplate($html);
			$template = $template->render($variables);
			
			return $response = new Response(
				$template,
				Response::HTTP_OK,
				['content-type' => 'text/html']
			);
		}
		else{			
			throw $this->createNotFoundException('This page could not be found');
		}	
	}
}
