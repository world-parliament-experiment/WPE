<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Form\RegistrationForm;
use AppBundle\Form\VerifyForm;
use Symfony\Component\Form\FormFactoryInterface;
// use FOS\UserBundle\Model\UserManagerInterface;
use AppBundle\Service\UserManager;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use AppBundle\Service\Mailer;
use AppBundle\Service\SendOtpVerificationService;
use DateInterval;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\String\Slugger\AsciiSlugger;
use DateTime;
use Psr\Log\LoggerInterface;

class OtpVerificationController extends AbstractController
{
    /**
     * @var FormFactoryInterface
     * @Autowired
     */

    private $userManager;
    private $sendOtpService;

    public function __construct(UserManager $userManager, SendOtpVerificationService $sendOtpService)
    {
        $this->userManager = $userManager;
        $this->sendOtpService = $sendOtpService;
    }

    /**
     * @Route("/otp/confirmed", name="app_otp_confirmed")
     */
    public function confirmedAction(Request $request)
    {
        $userManager = $this->userManager;
        $sendOtpService = $this->sendOtpService;

        $user = $this->getUser();
        $form = $this->createForm(VerifyForm::class, $user);

        $this->sendOtpService->checkIfAlreadyVerified($user);
        $isExpired = $this->sendOtpService->checkIfExpired($user);

        if($isExpired)
        {
            $this->addFlash('Alert', 'OTP is expired');
            return $this->render('registration/otp-verification.html.twig', array(
                'username' => $user->getUsername(),
                'user' => $user,
                'form' => $form->createView(),
                'targetUrl' => 'homepage',
            ));
        }

        $getOtp = $sendOtpService->generateOtp();
        $getExpireAt = $sendOtpService->setExpirationOfOtp();
        $user->setOtp($getOtp);
        $user->setExpireAt($getExpireAt);
        
        $userManager->updateUser($user);
        
        // $sendOtpService->send($user, $getOtp);        

        return $this->render('registration/otp-verification.html.twig', array(
            'username' => $user->getUsername(),
            'user' => $user,
            'form' => $form->createView(),
            'targetUrl' => 'homepage',
        ));
    }

    /**
     *@param Request $request
     * @return RedirectResponse|Response
     * @Route("/otp/get-otp", name="app_otp_getotp")
     */
    public function getOtp(Request $request)
    {
        $user = $this->getUser();
        $form = $this->createForm(VerifyForm::class, $user);
        dd($request->request->get('verify_form'));
        $userEnteredNumber = $request->request->get('verify_form')['mobileNumber'] ?? null;
        $this->sendOtpService->checkIfAlreadyVerified($user);

        $otp = $this->sendOtpService->generateOtp();
        $getExpireAt = $this->sendOtpService->setExpirationOfOtp();
        $user->setOtp($otp);
        $user->setExpireAt($getExpireAt);

        if($userEnteredNumber !== null){
            $user->setMobileNumber($userEnteredNumber);
        }

        if ($userEnteredNumber != $user->getMobileNumber()) {
            $user->setVerifiedAt(null);
        }
        $this->userManager->updateUser($user);
        // $this->sendOtpService->send($user,$otp);
        return $this->redirectToRoute('app_otp_verification');
    }

    /**
     * 
     * @Route("/otp/verify-otp", name="app_otp_verify_otp")
     */
    public function verifyOtp(Request $request)
    {
        $user = $this->getUser();
        $userEnteredOtp = $request->request->get('verify_form')['otp'] ?? "";
        $form = $this->createForm(VerifyForm::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted()) {

            if($form->isValid()) {
                $this->sendOtpService->checkIfAlreadyVerified($user);
                $isExpired = $this->sendOtpService->checkIfExpired($user);

                if($isExpired)
                {
                    $this->addFlash('Alert', 'OTP is expired. Geneerate new otp..');
                    return $this->render('registration/otp-verification.html.twig', array(
                        'username' => $user->getUsername(),
                        'user' => $user,
                        'form' => $form->createView(),
                        'targetUrl' => 'homepage',
                    ));
                }
                if ($user->getOtp() !== $userEnteredOtp) {
                    $this->addFlash('Alert', 'OTP is incorrect');
                    return $this->redirectToRoute('app_otp_verification');
                } else {
                    $user->setConfirmationToken(null);
                    $user->setEnabled(true);
                    $user->setVerifiedAt(new DateTime());
                    $this->userManager->updateUser($user);
                    return $this->redirectToRoute('homepage');
                }
            }
        }
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/otp/verification", name="app_otp_verification")
     */
    public function redirectToVerification(Request $request)
    {
        $user = $this->getUser();
        $form = $this->createForm(VerifyForm::class, $user);

        return $this->render('registration/otp-verification.html.twig', array(
            'username' => $user->getUsername(),
            'user' => $user,
            'form' => $form->createView(),
        ));
    }
}
