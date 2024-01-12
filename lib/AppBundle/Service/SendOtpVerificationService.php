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

    public function __construct(LoggerInterface $logger,UserManager $userManager)
    {
        $this->logger = $logger;
        $this->userManager = $userManager;
    }
    public function send($user,$otp,$telePhoneCode): void
    {
        $url = $_ENV['API_URL'];
        $textMessage = (strlen($_ENV['SMS_MESSAGE']) > 11) ? self::SMS_MESSAGE : $_ENV['SMS_MESSAGE'];
        $message = sprintf($textMessage,$user->getUsername(),$otp);
        $phoneNumber = $telePhoneCode . $user->getMobileNumber();
      
        $headers = [
            'Content-Type' =>  $_ENV['SMS_CONTENT_TYPE'],
            'Authorization' => 'Token ' . $_ENV['SMS_AUTHORIZATION'],
        ];
    
        $options = [
            'form_params' => [
                'sender' => $_ENV['SMS_SENDER'],
                'message' => $message,
                'recipients.0.msisdn' => $phoneNumber
            ]
        ];
        try {
            $client = new Client();
            $request = new Request('POST',$url, $headers);
            $res = $client->sendAsync($request, $options)->wait();
            
        } catch (RequestException $e) {
            $this->logger->debug('Failed to send OTP:');
            $this->logger->debug('Request URI : ' . $url . json_encode($options) . $message);
            $this->logger->debug('Exception : ' . json_encode($e->getMessage()));
            throw new RequestException('Something went wrong..' ,$e->getRequest());
        }    
        catch (Throwable $e) {
            $this->logger->debug('Failed to send OTP:');
            $this->logger->debug('An error has occured while sending otp: ',['messasge' => $e->getMessage()]);
            throw new Exception('Something went wrong..');
        }   
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

    public function checkIfAlreadyVerified(User $user)
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