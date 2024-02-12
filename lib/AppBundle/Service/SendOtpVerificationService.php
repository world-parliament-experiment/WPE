<?php

namespace AppBundle\Service;

use DateInterval;
use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use AppBundle\Entity\User;
use Exception;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Notifier\Channel\SmsChannel;
use Throwable;

class SendOtpVerificationService
{
    public const SMS_MESSAGE = "WPE";
    private $logger;
    private $userManager;
    private $smsMessage;
    private $smsContentType;
    private $smsAuth;
    private $smsSender;
    private $smsApiUrl;

    public function __construct(
        LoggerInterface $logger,
        UserManager $userManager,
        string $smsMessage,
        string $smsContentType,
        string $smsAuth,
        string $smsSender,
        string $smsApiUrl
    )
    {
        $this->logger = $logger;
        $this->userManager = $userManager;
        $this->smsMessage = $smsMessage;
        $this->smsContentType = $smsContentType;
        $this->smsAuth = $smsAuth;
        $this->smsSender = $smsSender;
        $this->smsApiUrl = $smsApiUrl;
    }
    public function send($user,$otp,$telePhoneCode)
    {
        try {
            $url = $this->smsApiUrl;
            $message = sprintf($this->smsMessage,$user->getUsername(),$otp);
            $phoneNumber = preg_replace('/^\+/','',  $user->getMobileNumber());

            if ( $user->getMobileNumber() !== null && !preg_match('/^\+/', $user->getMobileNumber())) {

                $phoneNumber = $telePhoneCode . $user->getMobileNumber();
            }
 
            $headers = [
                'Content-Type' => $this->smsContentType,
                'Authorization' => 'Token ' . $this->smsAuth,
            ];

            $options = [
                'form_params' => [
                    'sender' => $this->smsSender,
                    'message' => $message,
                    'recipients.0.msisdn' => $phoneNumber
                ]
            ];
            $client = new Client();
            $request = new Request('POST',$url, $headers);
            $res = $client->sendAsync($request, $options)->wait();
            $this->logger->debug('Request URI : ' . $url . json_encode($options) . $message);

        } catch (RequestException $e) {
            $this->logger->debug('Failed to send OTP:');
            $this->logger->debug('Request URI : ' . $url . json_encode($options) . $message);
            $this->logger->debug('Exception : ' . json_encode($e->getMessage()));

            return false;
        }
        catch (Throwable $e) {
            $this->logger->debug('Failed to send OTP:');
            $this->logger->debug('An error has occured while sending otp: ',['messasge' => $e->getMessage()]);

            return false;
        }

        return true;
    }

    public function generateOtp(){
        $bytes = random_bytes(2);
        $otp = hexdec(bin2hex($bytes));
        $otp = str_pad($otp, 2, '0', STR_PAD_LEFT);
        return $otp;
    }

    public function setExpirationOfOtp(){
        $currentDateTime = new DateTime();

        $currentDateTime->add(new DateInterval('PT1M'))->format('Y-m-d H:i:s');

        return $currentDateTime;
    }

    public function checkIfExpired(User $user){
        $currentDate = new DateTime();
        if(null != $user->getExpireAt() && $currentDate > $user->getExpireAt()){
            return true;
        }
        return false;
    }

    public function checkIfAlreadyVerifiedOrNot(User $user)
    {
        if (null != $user->getVerifiedAt()) {
            $user->setConfirmationToken(null);
            $user->setEnabled(true);
            $this->userManager->updateUser($user);
            return true;
        }
        return false;
    }
}
