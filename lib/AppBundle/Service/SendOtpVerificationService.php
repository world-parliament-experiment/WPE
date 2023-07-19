<?php

namespace AppBundle\Service;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

class SendOtpVerificationService
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    public function send($user,$otp): void
    {
        $code = $_ENV['COUNTRY_CODE'];
        $instanceId = $_ENV['INSTANCE_ID'];
        $token = $_ENV['ACCESS_TOKEN'];
        $url = $_ENV['API_URL'];

        $message = urlencode("Hello ".$user->getUsername()."\n"."Your OTP (One time password) is:".$otp);
        $phoneNumber = $code . $user->getMobileNumber();
        try {
            $client = new Client();
            $request = new Request('GET',sprintf($url,$phoneNumber,$message,$instanceId,$token));
            $res = $client->sendAsync($request)->wait();
            
        } catch (RequestException $e) {
            $this->logger->debug('Failed to send OTP:');
            $this->logger->debug('Request URI : ' . sprintf($url,$phoneNumber,$message,$instanceId,$token));
            $this->logger->debug('Exception : ' . json_encode($e->getTrace()));
            throw new RequestException('Something went wrong..');
        }
    }

    public function generateOtp(){
        $bytes = random_bytes(2);
        $otp = hexdec(bin2hex($bytes));
        $otp = str_pad($otp, 3, '0', STR_PAD_LEFT);
        return $otp;
    }

}