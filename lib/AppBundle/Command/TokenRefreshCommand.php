<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Process\Process;

class TokenRefreshCommand extends Command
{
    protected static $defaultName = 'wpe:tokenrefresh';

    private $lkClientId;
    private $lkClientSecret;
    private $lkRefreshToken;
    private $httpClient;

    public function __construct(string $lkinClientId, string $lkinClientSecret, string $lkinRefreshToken)
    {
        parent::__construct();
        $this->lkClientId = $lkinClientId;
        $this->lkClientSecret = $lkinClientSecret;
        $this->lkRefreshToken = $lkinRefreshToken;
        $this->httpClient = new Client();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Refresh tokens for social media APIs')
            ->addOption('linkedin', 'l', InputOption::VALUE_NONE, 'Refresh LinkedIn Token');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('linkedin')) {
            $io->note('Refreshing LinkedIn token...');

           $currentRefreshToken = $this->lkRefreshToken;

            if (!$currentRefreshToken) {
                $io->error('No LKIN_REFRESH_TOKEN found in environment.');
                return Command::FAILURE;
            }

            try {
                $response = $this->httpClient->post('https://www.linkedin.com/oauth/v2/accessToken', [
                    'form_params' => [
                        'grant_type'    => 'refresh_token',
                        'refresh_token' => $currentRefreshToken,
                        'client_id'     => $this->clientId,
                        'client_secret' => $this->clientSecret,
                    ],
                ]);

                $data = json_decode($response->getBody(), true);

                // 2. UPDATE SECRETS PROGRAMMATICALLY
                // We use '-' as the second argument to tell Symfony to read from STDIN
                $this->updateSecret('LKIN_ACCESS_TOKEN', $data['access_token'], $io);
                $this->updateSecret('LKIN_REFRESH_TOKEN', $data['refresh_token'], $io);

                $io->success('LinkedIn tokens rotated successfully.');
                return Command::SUCCESS;

            } catch (\Exception $e) {
                $io->error('LinkedIn Refresh Failed: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $io->warning('No platform selected. Use --linkedin');
        return Command::INVALID;
    }

    private function updateSecret(string $name, string $value, SymfonyStyle $io): void
    {
        // The '-' argument is crucial: it tells secrets:set to read the value from our input pipe
        $process = new Process(['php', 'bin/console', 'secrets:set', $name, '-']);
        $process->setInput($value); 
        $process->run();

        if (!$process->isSuccessful()) {
            $io->error("Failed to set secret $name: " . $process->getErrorOutput());
        }
    }
}