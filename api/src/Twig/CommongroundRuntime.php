<?php
// src/Twig/Commonground.php
namespace App\Twig;

use Twig\Extension\RuntimeExtensionInterface;

use App\Service\CommonGroundService;

class CommongroundRuntime implements RuntimeExtensionInterface
{
	
	private $commongroundService;
	
	public function __construct(CommonGroundService $commongroundService)
	{
		$this->commongroundService = $commongroundService;
	}
	
	public function getFromCommonground($object)
	{
		return $this->commongroundService->get($object);
	}
}