<?php

namespace AppBundle\Controller;

use AppBundle\CountriesCodes;
use AppBundle\Entity\User;
use AppBundle\Form\GetOtpForm;
use AppBundle\Form\RegistrationForm;
use AppBundle\Form\VerifyForm;
use Psr\Log\LoggerInterface;
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
        $formData = [
            'mobileNumber' => $user->getMobileNumber(),
            'countryCode' => $this->searchCountryCode($user->getCountry())
        ];

        $data = $request->request->all();
        $formOtp = $this->createForm(GetOtpForm::class, $formData);
        $form = $this->createForm(VerifyForm::class, $user);

        $code = (count($data) == 0) ? $user->getCountry() : $data['get_otp_form']['countryCode'];
        $telephoneCode = $this->searchCountryCode($code);

        list('route' => $route, 'routeParams' => $routeParams) = $this->getRouteInfoFromSession();

        [$isVerified, $isExpired] = [
            $this->sendOtpService->checkIfAlreadyVerified($user),
            $this->sendOtpService->checkIfExpired($user),
        ];

        if($isVerified)
        {
            $this->addFlash('success', 'This number is already verified.');
            return $this->redirectToRoute($route,$routeParams);
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
                'selectedCountry' => $formData['countryCode']
            ));
        }
        if($user->getOtp() == null){
            $processedOtp = $this->processOtp($user);
            $this->userManager->updateUser($processedOtp['updatedUser']);

            if(! $this->sendOtpService->send($user,$processedOtp['otp'],$telephoneCode))
            {
                $this->addFlash("danger", "An error has occured.While sending otp");
            } else {
                $this->addFlash('success', 'Your OTP is generated successfully..');
            }
        }

        return $this->render('registration/otp-verification.html.twig', array(
            'resend' => false,
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
        $user = $this->getUser();
        $formData = [
            'mobileNumber' => $user->getMobileNumber(),
            'countryCode' => $this->searchCountryCode($user->getCountry())
        ];

        $data = $request->request->all();
        $formOtp = $this->createForm(GetOtpForm::class, $formData);
        $form = $this->createForm(VerifyForm::class, $user);
        $this->get('session')->getFlashBag()->clear();
        list('route' => $route, 'routeParams' => $routeParams) = $this->getRouteInfoFromSession();

        $code = (count($data) == 0) ? $user->getCountry() : $data['get_otp_form']['countryCode'];

        $telephoneCode = $this->searchCountryCode($code);

        $this->logger->info("Code and telefone :" ,[$code,$telephoneCode]);
        $isVerified = $this->sendOtpService->checkIfAlreadyVerified($user);

        if ($isVerified) {
            $this->addFlash('success', 'This number is already verified.');
            return $this->redirectToRoute($route,$routeParams);
        }

        $formOtp->handleRequest($request);
        $processedOtp = $this->processOtp($user);

        if ($formOtp->isSubmitted() && $formOtp->isValid()) {
            $telephoneCode = $formOtp->get('countryCode')->getData();

            $userEnteredNumber = $formOtp->get('mobileNumber')->getData() ?? null;
            if($userEnteredNumber !== null){
                $user->setMobileNumber($userEnteredNumber);
            }

            if ($userEnteredNumber !== null && $userEnteredNumber !== $user->getMobileNumber()) {
                $user->setVerifiedAt(null);
            }
        }

        $this->userManager->updateUser($processedOtp['updatedUser']);

        if(! $this->sendOtpService->send($user,$processedOtp['otp'],$telephoneCode))
        {
            $this->addFlash("danger", "An error has occured.While sending otp");
        } else {
            $this->addFlash('success', 'Your OTP is generated successfully..');
        }
        return $this->render('registration/otp-verification.html.twig', array(
            'resend' => false,
            'user' => $user,
            'form' => $form->createView(),
            'formOtp' => $formOtp->createView(),
            'targetUrl' => 'homepage',
            'selectedCountry' => $formData['countryCode']
        ));
    }
    /**
     * @Route("/otp/verify-otp", name="app_otp_verify_otp")
     */
    public function verifyOtp(Request $request)
    {
        $user = $this->getUser();
        $form = $this->createForm(VerifyForm::class, $user);

        $storedOtp = $user->getOtp();
        list('route' => $route, 'routeParams' => $routeParams) = $this->getRouteInfoFromSession();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $userEnteredOtp = $form->get('otp')->getData() ?? null;

            if($form->isValid()) {
                [$isVerified, $isExpired] = [
                    $this->sendOtpService->checkIfAlreadyVerified($user),
                    $this->sendOtpService->checkIfExpired($user),
                ];

                if($isExpired) {
                    $this->addFlash('danger', 'Entered OTP is expired.');
                    return $this->redirectToRoute('app_otp_confirmed');
                }

                if($isVerified) {
                    $this->addFlash('danger', 'Entered OTP is expired.');
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
                    $this->addFlash('success', 'This number is verified successfully.');
                    return $this->redirectToRoute($route,$routeParams);
                }
            }
        }
    }

    private function processOtp(User $user)
    {
        $otp = $this->sendOtpService->generateOtp();
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

    public function searchCountryCode($code)
    {
        $keys = array_keys(CountriesCodes::COUNTRY_CODES);
        $keySearch = array_search($code, $keys);

        if ($keySearch !== false) {
            return $keys[$keySearch];
        }

        $valueSearch = array_search($code, CountriesCodes::COUNTRY_CODES);
        if ($valueSearch !== false) {
            return $valueSearch;
        }

        return null;
    }
}
