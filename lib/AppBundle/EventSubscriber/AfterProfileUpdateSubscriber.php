<?php


namespace AppBundle\EventSubscriber;

// use FOS\UserBundle\Event\FormEvent;
// use FOS\UserBundle\FOSUserEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AfterProfileUpdateSubscriber implements EventSubscriberInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    const PROFILE_EDIT_SUCCESS = 'PROFILE_EDIT_SUCCESS';
    const CHANGE_PASSWORD_SUCCESS = 'CHANGE_PASSWORD_SUCCESS';

    /**
     * AfterProfileUpdateSubscriber constructor.
     * @param UrlGeneratorInterface $router
     */
    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    public static function getSubscribedEvents()
    {
        return [
            self::PROFILE_EDIT_SUCCESS => 'onProfileEditSuccess',
            self::CHANGE_PASSWORD_SUCCESS => 'onChangePasswordSuccess',
        ];
    }

    public function onProfileEditSuccess(FormEvent $event)
    {
        $url = $this->router->generate('fos_user_profile_edit');
        $event->setResponse(new RedirectResponse($url));
    }

    public function onChangePasswordSuccess(FormEvent $event)
    {
        $url = $this->router->generate('fos_user_change_password');
        $event->setResponse(new RedirectResponse($url));
    }

}