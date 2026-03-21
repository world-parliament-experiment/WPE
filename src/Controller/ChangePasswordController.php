<?php
namespace App\Controller;

use App\Form\ChangePasswordForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Throwable;

class ChangePasswordController extends AbstractController
{

    private $passwordHasher;
    private TokenStorageInterface $tokenStorage;
    private ManagerRegistry $managerRegistry;
    private  LoggerInterface $logger;

    public function __construct(UserPasswordHasherInterface $passwordHasher,TokenStorageInterface $tokenStorage, ManagerRegistry $managerRegistry,LoggerInterface $logger)
    {
        $this->passwordHasher = $passwordHasher;
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
                $encodedPassword = $this->passwordHasher->hashPassword($user, $newPassword);

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