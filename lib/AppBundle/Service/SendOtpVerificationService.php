<?php

namespace AppBundle\Service;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class SendOtpVerificationService
{ 
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
            dd($e);
            throw new RequestException('Something went wrong..');
        }
    }

    public function sendConfirmationEmailMessage($user)
    {
        // $template = $this->parameters['resetting.template'];
        $url = $this->router->generate('app_register_confirm', array('token' => $user->getConfirmationToken()), UrlGeneratorInterface::ABSOLUTE_URL);
        $rendered = $this->twig->render('registration/email.txt.twig', array(
            'user' => $user,
            'confirmationUrl' => $url,
        ));

        $renderedLines = explode("\n", trim($rendered));
        $subject = array_shift($renderedLines);
        $body = implode("\n", $renderedLines);

        $this->send((string) $user->getEmail(), $subject, $body, $this->senderEmail);
    }

    public function sendResettingEmailMessage($user)
    {
        $url = $this->router->generate('app_resetting_resetpass', array('token' => $user->getConfirmationToken()), UrlGeneratorInterface::ABSOLUTE_URL);
        $rendered = $this->twig->render('Resetting/email.txt.twig', array(
            'user' => $user,
            'confirmationUrl' => $url,
        ));

        $renderedLines = explode("\n", trim($rendered));
        $subject = array_shift($renderedLines);
        $body = implode("\n", $renderedLines);

        $this->send((string) $user->getEmail(), $subject, $body, $this->senderEmail);
    }


    
}