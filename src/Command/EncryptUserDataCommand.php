<?php

namespace App\Command;

use App\Service\UserEncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EncryptUserDataCommand extends Command
{
    protected static $defaultName = 'app:encrypt-user-data';

    private $entityManager;
    private $userEncryptionService;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserEncryptionService $userEncryptionService
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->userEncryptionService = $userEncryptionService;
    }

    protected function configure()
    {
        $this
            ->setDescription('Encrypts all unencrypted emails and mobile numbers in the user table.')
            ->setHelp('This command migrates legacy plain-text user data to securely encrypted strings.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Starting User Data Encryption Migration');

        $conn = $this->entityManager->getConnection();
        
        // Fetch all users
        $sql = 'SELECT id, email, email_canonical, mobile_number FROM fos_user';
        $stmt = $conn->executeQuery($sql);
        $users = $stmt->fetchAllAssociative();

        if (empty($users)) {
            $io->info('No users found.');
            return Command::SUCCESS;
        }

        $io->progressStart(count($users));
        $encryptedCount = 0;
        $skippedCount = 0;

        foreach ($users as $user) {
            $needsUpdate = false;
            $updateSql = 'UPDATE fos_user SET ';
            $updateParams = [];
            $updateTypes = [];

            // Check and encrypt email
            if (!empty($user['email']) && strpos(base64_decode($user['email'], true) ?: '', 'enc::') !== 0) {
                $updateSql .= 'email = :email, ';
                $updateParams['email'] = $this->userEncryptionService->encrypt($user['email']);
                $needsUpdate = true;
            }

            // Check and encrypt email_canonical
            if (!empty($user['email_canonical']) && strpos(base64_decode($user['email_canonical'], true) ?: '', 'enc::') !== 0) {
                $updateSql .= 'email_canonical = :email_canonical, ';
                $updateParams['email_canonical'] = $this->userEncryptionService->encrypt($user['email_canonical']);
                $needsUpdate = true;
            }

            // Check and encrypt mobile_number
            if (!empty($user['mobile_number']) && strpos(base64_decode($user['mobile_number'], true) ?: '', 'enc::') !== 0) {
                $updateSql .= 'mobile_number = :mobile_number, ';
                $updateParams['mobile_number'] = $this->userEncryptionService->encrypt($user['mobile_number']);
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                // Remove trailing comma and space
                $updateSql = rtrim($updateSql, ', ');
                $updateSql .= ' WHERE id = :id';
                $updateParams['id'] = $user['id'];

                $conn->executeStatement($updateSql, $updateParams);
                $encryptedCount++;
            } else {
                $skippedCount++;
            }

            $io->progressAdvance();
        }

        $io->progressFinish();
        
        $io->success(sprintf('Migration complete! Encrypted %d user(s), skipped %d already-encrypted user(s).', $encryptedCount, $skippedCount));

        return Command::SUCCESS;
    }
}
