<?php


namespace AppBundle\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

/*
 * https://stackoverflow.com/questions/50861157/symfony-3-4-http-cache-always-cache-control-max-age-0-must-revalidate-priva/50873737
 * In my case i just needed the shared cached headers for a specific controller.
 */

class ResponseListener
{
    public function onKernelResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();

        $controller = $event->getRequest()->attributes->get('_controller');
        $requiredAssetAction = "AppBundle\Controller\UserController::avatarAction";

        if ($controller == $requiredAssetAction) {
            $response->headers->addCacheControlDirective('max-age', 900);
            $response->headers->addCacheControlDirective('s-maxage', 900);
            $response->headers->addCacheControlDirective('must-revalidate', true);
            $response->headers->addCacheControlDirective('public', true);
            $response->headers->removeCacheControlDirective('private');

        }

        $event->setResponse($response);
    }

}