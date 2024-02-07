<?php
namespace AppBundle\Controller;

use AppBundle\Form\ChangePasswordForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Throwable;

class ChangePasswordController extends AbstractController
{

    private $passwordEncoder;
    private TokenStorageInterface $tokenStorage;
    private ManagerRegistry $managerRegistry;
    private  LoggerInterface $logger;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder,TokenStorageInterface $tokenStorage, ManagerRegistry $managerRegistry,LoggerInterface $logger)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->tokenStorage = $tokenStorage;
        $this->managerRegistry = $managerRegistry;
        $this->logger = $logger;
    }

    /**
     * @Route("/changePassword", methods={"GET","POST"}, name="app_change_password")
     */
    public function changePassword(Request $request): Response
    {
        try {
            // Get the current user object
            if(! $user = $this->getUser()){
                $this->tokenStorage->setToken(null);
                return $this->redirectToRoute('app_login');
            }

            // Create a new form to handle the password change
            $form = $this->createForm(ChangePasswordForm::class);
            $form->handleRequest($request);

            // Handle form submission
            if ($form->isSubmitted() && $form->isValid()) {
                // Get the new password from the form
                $newPassword = $form->get('newPassword')->getData();

                // Encode the new password using the password encoder service
                $encodedPassword = $this->passwordEncoder->encodePassword($user, $newPassword);

                // Set the user's new password
                $user->setPassword($encodedPassword);

                $entityManager = $this->managerRegistry->getManager();
                $entityManager->flush();

                // Redirect the user to a success page
                return $this->redirectToRoute('homepage');
            }

            $errors = $form->getErrors(true, false);
        } catch(Throwable $exception){
            $this->logger->error('An exception occured while changing password.',['message' => $exception->getMessage(), 'trace' => $exception->getTrace()]);

            $this->addFlash('danger', 'Something went wrong while changing password..');
            return $this->redirectToRoute('app_login');
        }

        // Render the password change form
        return $this->render('ChangePassword/change_password.html.twig', [
            'form' => $form->createView(),
            'errors' => $errors
        ]);
    }
}
