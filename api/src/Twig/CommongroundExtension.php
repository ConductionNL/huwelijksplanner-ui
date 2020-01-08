<?php
// src/Twig/CommongroundExtension.php
namespace App\Twig;

use App\Twig\AppRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;;
use Twig\TwigFunction;

use  App\Twig\CommongroundRuntime;

class CommongroundExtension extends AbstractExtension
{	
	public function getFunctions()
	{
		return [
				// the logic of this filter is now implemented in a different class
				new TwigFunction('commonground', [CommongroundRuntime::class, 'getFromCommonground']),
		];
	}
}