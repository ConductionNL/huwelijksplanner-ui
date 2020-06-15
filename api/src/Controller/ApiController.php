<?php

namespace App\Controller;

use App\Entity\CacheItem;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    /**
     * @Route("api/cache_items")
     *
     * @param Request             $request
     * @param CommonGroundService $commonGroundService
     *
     * @return CacheItem
     */
    public function cacheDeleteAction(Request $request, CommonGroundService $commonGroundService): CacheItem
    {
        $resource = json_decode($request->getContent(), true);
        $commonGroundService->clearFromsCash(null, $resource['href']);

        $response = ['href'=>$resource['href'], 'message'=>'removed resource from cache'];
        $cacheItem = new CacheItem();
        $cacheItem->setHref($resource['href']);

        return $cacheItem;
    }
}
