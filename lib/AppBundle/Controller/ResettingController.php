<?php
namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Service\UserManager;
use AppBundle\Form\ResettingForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use AppBundle\Service\Mailer;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class ResettingController extends AbstractController
{

    private $userManager;
    private $tokenGenerator;
    private $mailer;
    private $managerRegistry;
    private $passwordEncoder;

    public function __construct(TokenGeneratorInterface $tokenGenerator, UserManager $userManager,  Mailer $mailer,ManagerRegistry $managerRegistry,UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->tokenGenerator = $tokenGenerator;
        $this->userManager = $userManager;
        $this->mailer = $mailer;
        $this->managerRegistry = $managerRegistry;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @Route("/resetting/request", name="app_resetting_request")
     */
    public function request()
    {
        return $this->render('Resetting/request.html.twig');
    }

    /**
     * @Route("/resetting/send-email", name="app_resetting_sendmail")
     */
    public function sendEmail(Request $request)
    {
        $username = $request->request->get('username');
        $user = $this->userManager->findUserByEmail($username);
        if (!$user) {
            $user = $this->userManager->getUserByUsername($username);
        }

        if (!$user) {
            throw new NotFoundHttpException(sprintf('No such email or user exists'));
        }

        $user->setEnabled(false);
        if (null === $user->getConfirmationToken()) {
            $user->setConfirmationToken($user->generateToken());
        }

        $entityManager = $this->managerRegistry->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        if (null !== $user) {
     
            $this->mailer->sendResettingEmailMessage($user);
            $user->setPasswordRequestedAt(new \DateTime());
            $this->userManager->updateUser($user);
     
        }

        return new RedirectResponse($this->generateUrl('app_resetting_checkmail', array('username' => $username)));
    }

    /**
     * @Route("/resetting/check-email", name="app_resetting_checkmail")
     */
    public function checkEmail(Request $request)
    {
        $username = $request->query->get('username');

        if (empty($username)) {
            // the user does not come from the sendEmail action
            return new RedirectResponse($this->generateUrl('app_resetting_request'));
        }

        return $this->render('Resetting/check_email.html.twig', array(
            // 'tokenLifetime' => ceil($this->retryTtl / 3600),
            'tokenLifetime' => ceil(0 / 3600),
        ));
    }

    /**
     * @Route("/resetting/reset/{token}", name="app_resetting_resetpass")
     */
    public function resetPassword(Request $request, $token)
    {
        $userManager = $this->userManager;

        $user = $userManager->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with confirmation token "%s" does not exist', $token));
        }

        $form = $this->createForm(ResettingForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tokenKey = new UsernamePasswordToken($user, null, 'main', $user->getRoles());

            $newPassword = $form->get('plainPassword')->getData();
            
            // Encode the new password using the password encoder service
            $encodedPassword = $this->passwordEncoder->encodePassword($user, $newPassword);

            // Set the user's new password
            $user->setPassword($encodedPassword);
            $user->setConfirmationToken(null);
            $user->setEnabled(true);
            // $userManager->updateUser($user);
            $userManager->updateUser($user);

            // Set the token in the security context
            $this->get('security.token_storage')->setToken($tokenKey);

            return $this->redirectToRoute('app_profile_show');

        }

        return $this->render('Resetting/reset.html.twig', array(
            'token' => $token,
            'form' => $form->createView(),
        ));
    }
}