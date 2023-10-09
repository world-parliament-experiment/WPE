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
use AppBundle\Service\SendOtpVerificationService;
use DateTime;
use Symfony\Component\HttpKernel\KernelInterface;

class OtpVerificationController extends AbstractController
{
    /**
     * @var FormFactoryInterface
     * @Autowired
     */
    private const DEFAULT_OTP = 1234; 
    private $userManager;
    private $sendOtpService;
    private $env;

    public function __construct(UserManager $userManager, SendOtpVerificationService $sendOtpService,KernelInterface $kernel)
    {
        $this->userManager = $userManager;
        $this->sendOtpService = $sendOtpService;
        $this->env = $kernel->getEnvironment();
    }

    /**
     * @Route("/otp/confirmed", name="app_otp_confirmed")
     */
    public function confirmedAction(Request $request)
    {
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
            $processedOtp = $this->processOtp($user);
            $this->userManager->updateUser($processedOtp['updatedUser']);
            $this->sendOtpService->send($user,$processedOtp['otp']);   
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
        $processedOtp = $this->processOtp($user);
        
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
        
        $this->userManager->updateUser($processedOtp['updatedUser']);
        $this->sendOtpService->send($user,$processedOtp['otp']);
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


    private function processOtp(User $user) 
    {
        $otp = self::DEFAULT_OTP;
        if($this->env === 'dev'){
            $getExpireAt = $this->sendOtpService->setExpirationOfOtp();
            $user->setOtp($otp);
            $user->setExpireAt($getExpireAt);
        } else {
            $otp = $this->sendOtpService->generateOtp();
            $getExpireAt = $this->sendOtpService->setExpirationOfOtp();
            $user->setOtp($otp);
            $user->setExpireAt($getExpireAt);     
        }

        return ['updatedUser' => $user,'otp' => $otp];
    }
}
