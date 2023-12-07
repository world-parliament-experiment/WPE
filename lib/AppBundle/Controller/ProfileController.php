<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

// use FOS\UserBundle\Event\FilterUserResponseEvent;
// use FOS\UserBundle\Event\FormEvent;
// use FOS\UserBundle\Event\GetResponseUserEvent;
// use FOS\UserBundle\FOSUserEvents;
use AppBundle\Form\ProfileForm;
use Symfony\Component\Form\FormFactoryInterface;
use AppBundle\Service\UserInterface;
use AppBundle\Service\UserManager;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Controller managing the user profile.
 *
 * @author Christophe Coevoet <stof@notk.org>
 */
class ProfileController extends AbstractController
{
    private $formFactory;
    private $userManager;

    public function __construct(FormFactoryInterface $formFactory, UserManager $userManager)
    {
        $this->formFactory = $formFactory;
        $this->userManager = $userManager;
    }

     /**
     * @Route("/profile", name="app_profile_show")
     */
    public function showAction()
    {
        $user = $this->getUser();
        if (!is_object($user)) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        return $this->render('Profile/show.html.twig', array(
            'user' => $user,
        ));
    }

    /**
     * Edit the user.
     *
     * @param Request $request
     *
     * @return Response
     */

    /**
     * @Route("/profile/edit", name="user_profile_edit")
     */
    public function editAction(Request $request)
    {
        $user = $this->getUser();

        $this->get('session')->set('route', $request->get('_route'));
        $this->get('session')->set('routeParams',[]);
        if (!is_object($user)) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }
        $oldPhoneNumber = $user->getMobileNumber(); 
        $form = $this->createForm(ProfileForm::class, $user);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
      
            $this->userManager->updateUser($user);
            
            $this->addFlash('success', 'The profile has been updated.');
            if($oldPhoneNumber != null || $oldPhoneNumber != $form->get('mobileNumber')->getData()){
                return $this->redirectToRoute('app_otp_getotp');
            }
        }

        return $this->render('Profile/edit.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
