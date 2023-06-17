<?php

namespace AppBundle\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Templating\EngineInterface;
use Appbundle\Entity\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;


class Mailer
{
    private $mailer;
    private $twig;
    private $router;
    private $senderEmail;

    public function __construct(MailerInterface $mailer, Environment $twig, RouterInterface $router, $senderEmail)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->router = $router;
        $this->senderEmail = $senderEmail;
    }

    public function send(string $to, string $subject, string $body, string $from): void
    {
        $email = (new Email())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->text($body);

        $this->mailer->send($email);
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