<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Form\RegistrationForm;
use Symfony\Component\Form\FormFactoryInterface;
// use FOS\UserBundle\Model\UserManagerInterface;
use AppBundle\Service\UserManager;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use AppBundle\Service\Mailer;
use Doctrine\Persistence\ManagerRegistry;

class RegistrationController extends AbstractController
{
    /**
     * @var FormFactoryInterface
     * @Autowired
     */
    private $formFactory;
    private $managerRegistry;
    private $userManager;
    private $mailer;

    public function __construct(FormFactoryInterface $formFactory, UserManager $userManager, Mailer $mailer,ManagerRegistry $managerRegistry)
    {
        $this->formFactory = $formFactory;
        $this->userManager = $userManager;
        $this->mailer = $mailer;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @Route("/register", name="app_register")
     */
    public function registerAction(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationForm::class, $user);
        $form->handleRequest($request);
        // $user->setEnabled(true);
        
        if ($form->isSubmitted()) {

            if($form->isValid()) {
                $user->setRegisteredAt(new \DateTime());
                $user->setUsernameCanonical($form->get('username')->getData());
                $user->setEmailCanonical($form->get('email')->getData());

                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                );
                $user->setPassword($hashedPassword);
                $user->setRoles($user->getRoles());

                $user->setEnabled(true);
                if (null === $user->getConfirmationToken()) {
                    $user->setConfirmationToken($user->generateToken());
                }

                $entityManager = $this->managerRegistry->getManager();
                $entityManager->persist($user);
                $entityManager->flush();

                // Email
                        // $user = $event->getForm()->getData();


                        $this->mailer->sendConfirmationEmailMessage($user);

                        $request->getSession()->set('fos_user_send_confirmation_email/email', $user->getEmail());

                        // $url = $this->router->generate('fos_user_registration_check_email');
                        // $event->setResponse(new RedirectResponse($url));

                        return $this->redirectToRoute('app_register_checkmail');



                        // Redirect to a success page or do something else
                        // return $this->redirectToRoute('homepage');
                    }
                }

         $errors = $form->getErrors(true, false);
         // dd($errors);

         return $this->render('registration/register.html.twig', [
             'registrationForm' => $form->createView(),
             'errors' => $errors
         ]);
    }

    /**
     * @Route("/register/confirm/{token}", name="app_register_confirm")
     */
    public function confirmAction(Request $request, $token)
    {
        $userManager = $this->userManager;

        $user = $userManager->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with confirmation token "%s" does not exist', $token));
        }

        $tokenKey = new UsernamePasswordToken($user, null, 'main', $user->getRoles());

        // Set the token in the security context
        $this->get('security.token_storage')->setToken($tokenKey);

        $user->setConfirmationToken(null);
        $user->setEnabled(true);

        $userManager->updateUser($user);

        return $this->redirectToRoute('app_register_confirmed');
    }

    /**
     * @Route("/register/confirmed", name="app_register_confirmed")
     */
    public function confirmedAction(Request $request)
    {
        $user = $this->getUser();

        return $this->render('registration/confirmed.html.twig', array(
            'username' => $user->getUsername(),
            'user' => $user,
            'targetUrl' => 'homepage',
        ));
    }

     /**
     * @Route("/register/check-email", name="app_register_checkmail")
     */
    public function checkEmailAction(Request $request)
    {
        $email = $request->getSession()->get('fos_user_send_confirmation_email/email');

        if (empty($email)) {
            return $this->redirectToRoute('app_register');
            // return new RedirectResponse($this->generateUrl('fos_user_registration_register'));
        }

        $request->getSession()->remove('fos_user_send_confirmation_email/email');
        $user = $this->userManager->findUserByEmail($email);

        return $this->render('registration/check_email.html.twig', array(
            'user' => $user,
        ));
    }
}
