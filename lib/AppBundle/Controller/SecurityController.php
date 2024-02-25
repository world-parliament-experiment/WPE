<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    private $authenticationUtils;
    private $tokenManager;

    public function __construct(AuthenticationUtils $authenticationUtils, CsrfTokenManagerInterface $tokenManager = null)
    {
        $this->authenticationUtils = $authenticationUtils;
        $this->tokenManager = $tokenManager;
    }

    /**
     * @Route("/login", name="app_login")
     */
    public function login(): Response
    {
        // Get the login error if there is one
        $error = $this->authenticationUtils->getLastAuthenticationError();

        // Get the last username entered by the user
        $lastUsername = $this->authenticationUtils->getLastUsername();

        $csrfToken = $this->tokenManager
            ? $this->tokenManager->getToken('authenticate')->getValue()
            : null;

        // dd($lastUsername);


        // Render the login form
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'csrf_token' => $csrfToken,
        ]);
    }

}
