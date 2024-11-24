<?php

namespace AppBundle\Service;

use AppBundle\CountriesCodes;
use DateInterval;
use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use AppBundle\Entity\User;
use Exception;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Notifier\Channel\SmsChannel;
use Throwable;

class SendOtpVerificationService
{
    private $logger;
    private $userManager;
    private $smsMessage;
    private $smsContentType;
    private $smsAuth;
    private $smsSender;
    private $smsApiUrl;
    private $smsAccID;

    public function __construct(
        LoggerInterface $logger,
        UserManager $userManager,
        string $smsMessage,
        string $smsContentType,
        string $smsAuth,
        string $smsSender,
        string $smsApiUrl,
        string $smsAccID
    )
    {
        $this->logger = $logger;
        $this->userManager = $userManager;
        $this->smsMessage = $smsMessage;
        $this->smsContentType = $smsContentType;
        $this->smsAuth = $smsAuth;
        $this->smsSender = $smsSender;
        $this->smsApiUrl = $smsApiUrl;
        $this->smsAccID = $smsAccID;
    }
    public function send($user,$otp,$telePhoneCode)
    {
        try {
            $url = $this->smsApiUrl;
            $message = sprintf($this->smsMessage,$user->getUsername(),$otp); 
            $phoneNumber = $user->getMobileNumber();
                // Add country code if not already present
            if (!preg_match('/^\+/', $phoneNumber)) {
                // Ensure country code starts with "+"
                if (!preg_match('/^\+/', $telePhoneCode)) {
                    $telePhoneCode = '+' . $telePhoneCode;
                }
                $phoneNumber = $telePhoneCode . $phoneNumber;
            }
            $headers = [
                'Authorization' => 'Basic ' . base64_encode("{$this->smsAccID}:{$this->smsAuth}"),
            ];

            // Prepare the request body
            $options = [
                'form_params' => [
                    'From' => $this->smsSender,  
                    'Body' => $message,
                    'To' => $phoneNumber,       // Formatted phone number
                ],
            ];
            $client = new Client();
            $response = $client->post($url, [
                'headers' => $headers,
                'form_params' => $options['form_params'],
            ]);

            // Log success
            $this->logger->info('SMS sent successfully. Response: ' . $response->getBody());

        } catch (RequestException $e) {
            $this->logger->error('Failed to send OTP:');
            $this->logger->error('Request URI : ' . $url . json_encode($options) . $message);
            $this->logger->error('Exception : ' . json_encode($e->getMessage()));

            return false;
        }
        catch (Throwable $e) {
            $this->logger->error('Failed to send OTP:');
            $this->logger->error('An error has occured while sending OTP: ',['messasge' => $e->getMessage()]);

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

    public function searchCountryCode($code)
    {
        $keys = array_keys(CountriesCodes::COUNTRY_CODES);
        $keySearch = array_search($code, $keys,true);
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
