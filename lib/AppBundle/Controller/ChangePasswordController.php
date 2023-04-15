<?php
namespace AppBundle\Controller;

use AppBundle\Form\ChangePasswordForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ChangePasswordController extends AbstractController
{

    private $passwordEncoder;

    // Inject the password encoder service into your controller
    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @Route("/changePassword", methods={"GET","POST"}, name="app_change_password")
     */
    public function changePassword(Request $request): Response
    {
        // Get the current user object
        $user = $this->getUser();

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

            // Update the user in your database
            $entityManager = $this->managerRegistry->getManager();
            $entityManager->flush();

            // Redirect the user to a success page
            return $this->redirectToRoute('homepage');
        }

        $errors = $form->getErrors(true, false);

        // Render the password change form
        return $this->render('ChangePassword/change_password.html.twig', [
            'form' => $form->createView(),
            'errors' => $errors
        ]);
    }
}
