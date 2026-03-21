<?php

namespace App\Command;

use App\Service\SendOtpVerificationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\User;

class TestOtpCommand extends Command
{
    protected static $defaultName = 'app:test-otp';

    private $sendOtpService;

    public function __construct(SendOtpVerificationService $sendOtpService)
    {
        $this->sendOtpService = $sendOtpService;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Sends a test OTP to a given phone number.')
            ->addArgument('phoneNumber', InputArgument::REQUIRED, 'The phone number to send the OTP to.')
            ->addArgument('countryCode', InputArgument::REQUIRED, 'The country code for the phone number.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $phoneNumber = $input->getArgument('phoneNumber');
        $countryCode = $input->getArgument('countryCode');

        $user = new User();
        $user->setMobileNumber($phoneNumber);
        $user->setCountry($countryCode);
        $user->setUsername('Test User');

        $otp = $this->sendOtpService->generateOtp();

        $output->writeln("Sending OTP to {$countryCode}{$phoneNumber}...");

        if ($this->sendOtpService->send($user, $otp, $countryCode)) {
            $output->writeln("Successfully sent OTP: {$otp}");
        } else {
            $output->writeln("Failed to send OTP.");
        }

        return Command::SUCCESS;
    }
}
