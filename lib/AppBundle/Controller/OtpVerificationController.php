<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Form\GetOtpForm;
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
        $formOtp = $this->createForm(GetOtpForm::class, $user);
        $form = $this->createForm(VerifyForm::class, $user);
        
        $isVerified = $this->sendOtpService->checkIfAlreadyVerified($user);
        $isExpired = $this->sendOtpService->checkIfExpired($user);
        
        if($isVerified)
        {
            $this->addFlash('success', 'This number is already verified.');
            return $this->redirectToRoute('homepage');
        }
        if($isExpired)
        {
            $this->addFlash('danger', 'Your OTP is expired.Kindly generate a new otp');
            return $this->render('registration/otp-verification.html.twig', array(
                'resend' => true,
                'user' => $user,
                'formOtp' => $formOtp->createView(),
                'form' => $form->createView(),
                'targetUrl' => 'homepage',
            ));
        }
        
        if($user->getOtp() == null){
            $getOtp = $sendOtpService->generateOtp();
            $getExpireAt = $sendOtpService->setExpirationOfOtp();
            $user->setOtp($getOtp);
            $user->setExpireAt($getExpireAt);
            
            $userManager->updateUser($user);
            $sendOtpService->send($user, $getOtp);        
            $this->addFlash('success', 'Your OTP is generated successfully..');
        }
        
        
        return $this->render('registration/otp-verification.html.twig', array(
            'resend' => false,
            'user' => $user,
            'form' => $form->createView(),
            'formOtp' => $formOtp->createView(),
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
        $formOtp = $this->createForm(GetOtpForm::class, $user);
        
        $this->sendOtpService->checkIfAlreadyVerified($user);
        
        $formOtp->handleRequest($request);
        
        $otp = $this->sendOtpService->generateOtp();
        $getExpireAt = $this->sendOtpService->setExpirationOfOtp();
        $user->setOtp($otp);
        $user->setExpireAt($getExpireAt);
        if ($formOtp->isSubmitted()) {
            if($formOtp->isValid()) {            
                $userEnteredNumber = $formOtp->get('mobileNumber')->getData() ?? null;
                
                if($userEnteredNumber !== null){
                    $user->setMobileNumber($userEnteredNumber);
                }
                
                if ($userEnteredNumber != null && $userEnteredNumber != $user->getMobileNumber()) {
                    $user->setVerifiedAt(null);
                }
            }
        }
        
        $this->userManager->updateUser($user);
        $this->sendOtpService->send($user,$otp);
        $this->addFlash('success', 'Your OTP is generated successfully..');
        return $this->redirectToRoute('app_otp_confirmed');
    }
    
    /**
     * 
     * @Route("/otp/verify-otp", name="app_otp_verify_otp")
     */
    public function verifyOtp(Request $request)
    {
        $user = $this->getUser();
        $form = $this->createForm(VerifyForm::class, $user);

        $storedOtp = $user->getOtp();
        $form->handleRequest($request);
        
        if ($form->isSubmitted()) {
            $userEnteredOtp = $form->get('otp')->getData() ?? null;

            if($form->isValid()) {
                $this->sendOtpService->checkIfAlreadyVerified($user);
                $isExpired = $this->sendOtpService->checkIfExpired($user);
                if($isExpired)
                {
                    return $this->redirectToRoute('app_otp_confirmed');
                }
                if ($storedOtp !== $userEnteredOtp) {
                    $this->addFlash('danger', 'Entered OTP is incorrect.');
                    return $this->redirectToRoute('app_otp_confirmed');
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
