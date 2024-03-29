<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Util;

// use FOS\UserBundle\Event\UserEvent;
// use FOS\UserBundle\FOSUserEvents;
// use FOS\UserBundle\Model\UserInterface;
// use FOS\UserBundle\Model\UserManagerInterface;
use AppBundle\Service\UserManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Executes some manipulations on the users.
 *
 * @author Christophe Coevoet <stof@notk.org>
 * @author Luis Cordova <cordoval@gmail.com>
 */
class UserManipulator
{
    /**
     * User manager.
     *
     * @var UserManager
     */
    private $userManager;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var RequestStack
     */
    private $requestStack;

    private $passwordHasher;

    /**
     * UserManipulator constructor.
     *
     * @param UserManager     $userManager
     * @param EventDispatcherInterface $dispatcher
     * @param RequestStack             $requestStack
     */
    public function __construct(UserManager $userManager, EventDispatcherInterface $dispatcher, UserPasswordHasherInterface $passwordHasher, RequestStack $requestStack)
    {
        $this->userManager = $userManager;
        $this->dispatcher = $dispatcher;
        $this->requestStack = $requestStack;
        $this->passwordHasher = $passwordHasher;

        
    }

    /**
     * Creates a user and returns it.
     *
     * @param string $username
     * @param string $password
     * @param string $email
     * @param bool   $active
     * @param bool   $superadmin
     *
     * @return \FOS\UserBundle\Model\UserInterface
     */
    public function create($username, $firstname, $lastname, $password, $email, $active, $superadmin)
    {
        $user = $this->userManager->createUser();
        $user->setUsername($username);
        $user->setFirstname($firstname);
        $user->setLastname($lastname);

        $user->setEmail($email);
        $user->setPlainPassword($password);
        $user->setEnabled((bool) $active);
        $user->setSuperAdmin((bool) $superadmin);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $password
        );
        $user->setPassword($hashedPassword);

        $user->setRegisteredAt(new \DateTime());
        $user->setUsernameCanonical($username);
        $user->setEmailCanonical($email);
        $user->setConsents(true);

        $this->userManager->updateUser($user);

        // $event = new UserEvent($user, $this->getRequest());
        // $this->dispatcher->dispatch(FOSUserEvents::USER_CREATED, $event);

        return $user;
    }

    /**
     * @return Request
     */
    private function getRequest()
    {
        return $this->requestStack->getCurrentRequest();
    }

}
