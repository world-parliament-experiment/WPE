<?php

namespace AppBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class LoginSubscriber implements EventSubscriberInterface
{
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public static function getSubscribedEvents()
    {
        return [
            'security.interactive_login' => 'onInteractiveLogin',
        ];
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        $token = $event->getAuthenticationToken();
        /** @var \AppBundle\Entity\User $user */
        // Get the user object from the authentication token
        $user = $token->getUser();

        if(!$user->isEnabled()) {
            $this->tokenStorage->setToken(null);
            // throw new UnauthorizedHttpException('Account not activated! Please check your mail');
        }
    }
}