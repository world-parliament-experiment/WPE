<?php

namespace App\Controller;

use App\CountriesCodes;
use App\Entity\User;
use App\Form\GetOtpForm;
use App\Form\RegistrationForm;
use App\Form\VerifyForm;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;
// use FOS\UserBundle\Model\UserManagerInterface;
use App\Service\UserManager;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\SendOtpVerificationService;
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

    private $logger;

    public function __construct(UserManager $userManager, SendOtpVerificationService $sendOtpService,KernelInterface $kernel,LoggerInterface $logger)
    {
        $this->userManager = $userManager;
        $this->sendOtpService = $sendOtpService;
        $this->env = $kernel->getEnvironment();
        $this->logger = $logger;
    }

    /**
     * @Route("/otp/confirmed", name="app_otp_confirmed")
     */
    public function confirmedAction(Request $request)
    {
        $user = $this->getUser();
        $telephoneCode = $this->sendOtpService->searchCountryCode($user->getCountry());
        $formData = [
            'mobileNumber' => $user->getMobileNumber(),
            'countryCode' => $telephoneCode
        ];

        $data = $request->request->all();
        
        $formOtp = $this->createForm(GetOtpForm::class, [
            'mobileNumber' => $user->getMobileNumber(),
            'country' => $telephoneCode
        ]);
        $form = $this->createForm(VerifyForm::class, $user);

        list('route' => $route, 'routeParams' => $routeParams) = $this->getRouteInfoFromSession();

        if($user->getOtp() == null){
            $processedOtp = $this->processOtp($user);
            $this->userManager->updateUser($processedOtp['updatedUser']);

            if(! $this->sendOtpService->send($user,$processedOtp['otp'],$telephoneCode))
            {
                $this->addFlash('danger', 'An error has occured while sending OTP');
            } else {
                $this->addFlash('success', 'Your OTP is generated successfully');
            }
        }

        return $this->render('registration/otp-verification.html.twig', array(
            'resend' => true,
            'user' => $user,
            'form' => $form->createView(),
            'formOtp' => $formOtp->createView(),
            'targetUrl' => 'homepage',
            'selectedCountry' => $formData['countryCode']
        ));
    }

    /**
     *@param Request $request
     * @return RedirectResponse|Response
     * @Route("/otp/get-otp", name="app_otp_getotp")
     */
    public function getOtp(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();
        $telephoneCode = $this->sendOtpService->searchCountryCode($user->getCountry());
        
        $formData = [
            'mobileNumber' => $user->getMobileNumber(),
            'countryCode' => $telephoneCode
        ];
        $data = $request->request->all();
      
        $formOtp = $this->createForm(GetOtpForm::class, [
            'mobileNumber' => $user->getMobileNumber(),
            'country' => $telephoneCode
        ]);
        
        $form = $this->createForm(VerifyForm::class, $user);
        $this->get('session')->getFlashBag()->clear();
        list('route' => $route, 'routeParams' => $routeParams) = $this->getRouteInfoFromSession();

        $formOtp->handleRequest($request);
        if ($formOtp->isSubmitted() && $formOtp->isValid()) {
            $formDataFromForm = $formOtp->getData();
            $telephoneCode = $formDataFromForm['country'];

            $userEnteredNumber = $formDataFromForm['mobileNumber'] ?? null;  
            if($userEnteredNumber !== null){
                $userEnteredNumber = (preg_match('/^0/',$userEnteredNumber) === 1) ? preg_replace('/^0/','',  $userEnteredNumber) : $userEnteredNumber;

                if ( $telephoneCode !== null && !preg_match('/^\+/',$userEnteredNumber)) {
                    $userEnteredNumber = '+' . $telephoneCode . $userEnteredNumber;
                }

                $user->setMobileNumber($userEnteredNumber);
            }

            if ($userEnteredNumber !== null && $userEnteredNumber !== $user->getMobileNumber()) {
                $user->setVerifiedAt(null);
            }
            
            $isoCode = $this->sendOtpService->searchCountryCode($telephoneCode);
            if ($isoCode) {
                $user->setCountry($isoCode);
            }
        }
        $processedOtp = $this->processOtp($user);
        $user->setVerifiedAt(null);
        $errors = $formOtp->getErrors(true, false)    ;
       
        if(count($errors) === 0){
            $this->userManager->updateUser($processedOtp['updatedUser']); 
            try {
                if(! $this->sendOtpService->send($user,$processedOtp['otp'],$telephoneCode))
                {
                    $this->addFlash('danger', 'An error has occured while sending OTP');
                } else {
                    $this->addFlash('success', 'Your OTP is generated successfully');
                }
            } catch(\Throwable $th) {
                $this->logger->error($th->getMessage(), $th->getTrace());
            }   
        } else {
            $this->addFlash('danger', 'An error has occured while sending OTP');
        }

        return $this->render('registration/otp-verification.html.twig', array(
            'resend' => false,
            'user' => $user,
            'form' => $form->createView(),
            'formOtp' => $formOtp->createView(),
            'targetUrl' => 'homepage',
            'selectedCountry' => $telephoneCode
        ));
    }
    /**
     * @Route("/otp/verify-otp", name="app_otp_verify_otp")
     */
    public function verifyOtp(Request $request)
    {
        $user = $this->getUser();
        $form = $this->createForm(VerifyForm::class);

        $storedOtp = $user->getOtp();
        list('route' => $route, 'routeParams' => $routeParams) = $this->getRouteInfoFromSession();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $userEnteredOtp = $form->get('otp')->getData() ?? null;
            
            if($form->isValid()) {
                [$isVerified, $isExpired] = [
                    $this->sendOtpService->checkIfAlreadyVerifiedOrNot($user),
                    $this->sendOtpService->checkIfExpired($user),
                ];

                if($isExpired) {
                    $this->addFlash('danger', 'Entered One-Time Password is expired.');
                    return $this->redirectToRoute('app_otp_confirmed');
                }

                if($isVerified) {
                    $this->addFlash('danger', 'Entered phone number is already verified.');
                    return $this->redirectToRoute('app_otp_confirmed');
                }
                if ($storedOtp !== $userEnteredOtp) {
                    $this->addFlash('danger', 'Entered One-Time Password is incorrect.');
                    return $this->redirectToRoute('app_otp_confirmed');
                } else {
                    $user->setConfirmationToken(null);
                    $user->setEnabled(true);
                    $user->setVerifiedAt(new DateTime());
                    $this->userManager->updateUser($user);
                    $this->addFlash('success', 'Your phone number has been verified successfully.');
                    return $this->redirectToRoute($route,$routeParams);
                }
            }
        }
        $this->addFlash('danger', 'Please enter valid One Time Password.');
        return $this->redirectToRoute('app_otp_confirmed');
    }

    /**
     * @Route("/otp/render-verfication", name="app_render_otp_form")
     */
    public function renderOtpForm(Request $request)
    {
        $formOtp = $this->createForm(GetOtpForm::class);
        
        return $this->render('registration/otp-verification-popup.html.twig', array(    
            'formOtp' => $formOtp->createView(),
            'resend' => false
        ));
    }

    private function processOtp(User $user)
    {
        if ($this->env === 'dev') {
            $otp = (string) self::DEFAULT_OTP;
        } else {
            $otp = $this->sendOtpService->generateOtp();
        }
        $getExpireAt = $this->sendOtpService->setExpirationOfOtp();
        $user->setOtp($otp);
        $user->setExpireAt($getExpireAt);

        return ['updatedUser' => $user,'otp' => $otp];
    }

    public function getRouteInfoFromSession()
    {
        return [
            'route' => $this->get('session')->get('route'),
            'routeParams' => $this->get('session')->get('routeParams'),
        ];
    }
}
