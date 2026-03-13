<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\UserImage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

use App\Service\AiAssistantService;
use Symfony\Component\Console\Input\InputOption;

class AiAvatarGenerateCommand extends Command
{
    protected static $defaultName = 'app:ai:generate-avatars';
    
    private $entityManager;
    private $projectDir;
    private $aiAssistant;

    public function __construct(
        EntityManagerInterface $entityManager,
        KernelInterface $kernel,
        AiAssistantService $aiAssistant
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->projectDir = $kernel->getProjectDir();
        $this->aiAssistant = $aiAssistant;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generates unique avatars for AI agents using DiceBear API')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force re-generation of avatars even if they exist')
            ->setHelp('This command fetches a unique PNG avatar for each AI agent and saves it to the avatars directory.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');
        
        $aiUsers = $this->entityManager->getRepository(User::class)->findBy(['isAi' => true]);
        $personas = $this->aiAssistant->getPersonas();
        
        if (empty($aiUsers)) {
            $io->warning('No AI agents found. Run app:ai:manage-agents first.');
            return Command::SUCCESS;
        }

        $avatarDir = $this->projectDir . '/public/assets/img/avatar/';
        if (!is_dir($avatarDir)) {
            mkdir($avatarDir, 0775, true);
        }

        foreach ($aiUsers as $user) {
            $io->section(sprintf('Processing avatar for: %s', $user->getUsername()));
            
            // Check if user already has a custom avatar (not default.png)
            $existingImage = $this->entityManager->getRepository(User::class)->getUserAvatarImage($user);
            if (!$force && $existingImage && $existingImage->getPath() !== 'default.png' && file_exists($avatarDir . $existingImage->getPath())) {
                $io->info('User already has a custom avatar. Skipping (use --force to overwrite).');
                continue;
            }

            // Get persona color for the robot face
            $personaKey = $user->getAiPersona();
            $faceColor = $personas[$personaKey]['color'] ?? 'b6e3f4';
            $bgColor = 'f0f0f0'; // Neutral light gray background for all

            // Generate avatar using DiceBear (bottts style)
            // baseColor controls the main color of the robot
            $seed = $user->getUsername();
            $apiUrl = sprintf(
                'https://api.dicebear.com/7.x/bottts/png?seed=%s&backgroundColor=%s&baseColor=%s', 
                urlencode($seed),
                $bgColor,
                $faceColor
            );
            
            try {
                $imageData = file_get_contents($apiUrl);
                if ($imageData === false) {
                    throw new \Exception('Failed to fetch image from API');
                }

                $filename = $user->getId() . '_ai_avatar_' . bin2hex(random_bytes(4)) . '.png';
                $filePath = $avatarDir . $filename;
                
                if (file_put_contents($filePath, $imageData) === false) {
                    throw new \Exception('Failed to save image to disk');
                }

                if (!$existingImage) {
                    $existingImage = new UserImage();
                    $existingImage->setUser($user);
                }
                
                $existingImage->setImageType(UserImage::USER_IMAGE_TYPE_AVATAR);
                $existingImage->setContentType('image/png');
                $existingImage->setPath($filename);

                $this->entityManager->persist($existingImage);
                $this->entityManager->flush();

                $io->success(sprintf('Generated and saved avatar: %s', $filename));
            } catch (\Exception $e) {
                $io->error(sprintf('Error generating avatar for %s: %s', $user->getUsername(), $e->getMessage()));
            }
        }

        $io->success('AI avatar generation process completed.');
        return Command::SUCCESS;
    }
}
