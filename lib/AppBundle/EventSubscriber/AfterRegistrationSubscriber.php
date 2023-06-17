<?php


namespace AppBundle\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
// use FOS\UserBundle\Event\FormEvent;
// use FOS\UserBundle\Event\GetResponseUserEvent;
// use FOS\UserBundle\FOSUserEvents;
// use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AfterRegistrationSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    const REGISTRATION_SUCCESS = 'REGISTRATION_SUCCESS';


    /**
     * AfterRegistrationSubscriber constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            self::REGISTRATION_SUCCESS  => 'onRegistrationSuccess'
        ];
    }

    public function onRegistrationSuccess(FormEvent $event)
    {
        $rolesArr = array('ROLE_USER');
        /** @var UserInterface $user */
        $user = $event->getForm()->getData();
        $user->setRoles($rolesArr);
    }
}