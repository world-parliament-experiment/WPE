<?php

namespace App\Command;

use App\Entity\Comment;
use App\Entity\Initiative;
use App\Entity\User;
use App\Entity\Category;
use App\Entity\Voting;
use App\Enum\InitiativeEnum;
use App\Enum\VotingEnum;
use App\Enum\CommentEnum;
use App\Service\AiAssistantService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use DateTime;

class AiAgentActionCommand extends Command
{
    protected static $defaultName = 'app:ai:run-agents';
    
    private $aiAssistant;
    private $entityManager;

    public function __construct(
        AiAssistantService $aiAssistant,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->aiAssistant = $aiAssistant;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Triggers AI agents to perform autonomous actions (drafting, commenting, replying, or reacting)')
            ->addArgument('action', InputArgument::REQUIRED, 'Action to perform: "draft", "comment", "reply", or "react"')
            ->addOption('persona', 'p', InputOption::VALUE_REQUIRED, 'Target a specific persona (if omitted, a random AI agent is chosen)')
            ->addOption('topic', 't', InputOption::VALUE_REQUIRED, 'Topic for drafting (if omitted, the agent will invent one)')
            ->addOption('category', 'c', InputOption::VALUE_REQUIRED, 'Category name for drafting (if omitted, a random Type 0 category is chosen)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action');
        $personaKey = $input->getOption('persona');

        // Find candidate AI users
        $criteria = ['isAi' => true];
        if ($personaKey) {
            $criteria['aiPersona'] = $personaKey;
        }
        
        $aiUsers = $this->entityManager->getRepository(User::class)->findBy($criteria);
        
        if (empty($aiUsers)) {
            $io->error('No AI agents found. Run app:ai:manage-agents first.');
            return Command::FAILURE;
        }

        // Pick one agent for this execution
        /** @var User $agent */
        $agent = $aiUsers[array_rand($aiUsers)];
        $personaKey = $agent->getAiPersona();

        $io->info(sprintf('Agent selected: %s (Persona: %s)', $agent->getUsername(), $personaKey));

        if ($action === 'draft') {
            return $this->handleDraft($input, $io, $agent);
        } elseif ($action === 'comment') {
            return $this->handleComment($io, $agent);
        } elseif ($action === 'reply') {
            return $this->handleReply($io, $agent);
        } elseif ($action === 'react') {
            return $this->handleReact($io, $agent);
        }

        $io->error('Invalid action. Use "draft", "comment", "reply", or "react".');
        return Command::FAILURE;
    }

    private function handleDraft(InputInterface $input, SymfonyStyle $io, User $agent): int
    {
        $topic = $input->getOption('topic');
        $categoryName = $input->getOption('category');
        $category = null;

        // 1. Resolve Category
        if ($categoryName) {
            $category = $this->entityManager->getRepository(Category::class)->findOneBy(['name' => $categoryName]);
            if (!$category) {
                $io->error(sprintf('Category "%s" not found.', $categoryName));
                return Command::FAILURE;
            }
        } else {
            // Pick a random Global (Type 0) category
            $globalCategories = $this->entityManager->getRepository(Category::class)->findBy(['type' => 0]);
            if (empty($globalCategories)) {
                $io->error('No Global (Type 0) categories found in database.');
                return Command::FAILURE;
            }
            $category = $globalCategories[array_rand($globalCategories)];
        }

        // 2. Resolve Topic (Autonomous Invention if missing)
        if (!$topic) {
            $io->info('No topic provided. Asking agent to invent one...');
            try {
                $topic = $this->aiAssistant->inventTopic($agent->getAiPersona(), $category->getName());
                $io->info(sprintf('Agent invented topic: "%s"', $topic));
            } catch (\Exception $e) {
                $io->error('Failed to invent topic: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }

        if ($category->getType() !== 0) {
            $io->error('AI agents can only draft in Type 0 categories.');
            return Command::FAILURE;
        }

        $io->info(sprintf('Drafting proposal about "%s" in category "%s"...', $topic, $category->getName()));

        try {
            $draft = $this->aiAssistant->draftFullInitiative($topic, $agent->getAiPersona());
            
            $slugger = new AsciiSlugger();
            $initiative = new Initiative();
            $voting = new Voting();

            $now = new DateTime();
            $initiative->setCategory($category);
            $initiative->setTitle($draft['title']);
            $initiative->setDescription($draft['description']);
            $initiative->setCreatedBy($agent);
            $initiative->setDuration("7");
            $initiative->setPublishedAt($now);
            $initiative->setCreatedAt($now);
            $initiative->setUpdatedAt($now);
            $initiative->setSlug($slugger);
            $initiative->setType(InitiativeEnum::TYPE_FUTURE);
            $initiative->setState(InitiativeEnum::STATE_ACTIVE);

            $voting->setStartdate($now);
            // End date 6 months later as in ScraperCommand
            $enddate = (clone $now)->modify('+6 months');
            $voting->setEnddate($enddate);
            $voting->setState(VotingEnum::STATE_WAITING);
            $voting->setType(VotingEnum::TYPE_FUTURE);
            $voting->setInitiative($initiative);

            $this->entityManager->persist($initiative);
            $this->entityManager->persist($voting);
            $this->entityManager->flush();

            $io->success(sprintf('New initiative created: %s (ID: %d)', $initiative->getTitle(), $initiative->getId()));
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to draft initiative: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function handleComment(SymfonyStyle $io, User $agent): int
    {
        // Find recent initiatives in Type 0 categories
        $qb = $this->entityManager->createQueryBuilder();
        $initiatives = $qb->select('i')
            ->from(Initiative::class, 'i')
            ->join('i.category', 'c')
            ->where('c.type = 0')
            ->andWhere('i.state = :state')
            ->setParameter('state', InitiativeEnum::STATE_ACTIVE)
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        if (empty($initiatives)) {
            $io->warning('No active global initiatives found to comment on.');
            return Command::SUCCESS;
        }

        /** @var Initiative $target */
        $target = $initiatives[array_rand($initiatives)];

        $io->info(sprintf('Commenting on: %s', $target->getTitle()));

        try {
            $commentText = $this->aiAssistant->generateComment(
                $target->getTitle(),
                $target->getDescription(),
                $agent->getAiPersona()
            );

            $comment = new Comment();
            $comment->setInitiative($target);
            $comment->setMessage($commentText);
            $comment->setCreatedBy($agent);
            $comment->setState(CommentEnum::STATE_OPEN);
            $comment->setLiked(0);
            $comment->setDisliked(0);
            $comment->setReported(0);
            $comment->setCreatedAt(new DateTime());
            $comment->setUpdatedAt(new DateTime());
            
            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $io->success('Comment posted successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to generate comment: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function handleReact(SymfonyStyle $io, User $agent): int
    {
        // 50% chance to react to initiative, 50% to comment
        if (rand(0, 1) === 0) {
            return $this->reactToInitiative($io, $agent);
        } else {
            return $this->reactToComment($io, $agent);
        }
    }

    private function reactToInitiative(SymfonyStyle $io, User $agent): int
    {
        $initiatives = $this->entityManager->getRepository(Initiative::class)
            ->createQueryBuilder('i')
            ->join('i.category', 'c')
            ->where('c.type = 0')
            ->andWhere('i.state = :state')
            ->setParameter('state', InitiativeEnum::STATE_ACTIVE)
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        if (empty($initiatives)) {
            $io->warning('No active global initiatives found to react to.');
            return Command::SUCCESS;
        }

        /** @var Initiative $target */
        $target = $initiatives[array_rand($initiatives)];

        $io->info(sprintf('Reacting to initiative: %s', $target->getTitle()));

        $reaction = $this->aiAssistant->decideReaction($target->getTitle(), $target->getDescription(), $agent->getAiPersona());

        if ($reaction === 'like') {
            $target->setLiked($target->getLiked() + 1);
            $io->success('Liked.');
        } elseif ($reaction === 'dislike') {
            $target->setDisliked($target->getDisliked() + 1);
            $io->success('Disliked.');
        } else {
            $io->info('Stayed neutral.');
        }

        $this->entityManager->flush();
        return Command::SUCCESS;
    }

    private function reactToComment(SymfonyStyle $io, User $agent): int
    {
        $comments = $this->entityManager->getRepository(Comment::class)
            ->createQueryBuilder('c')
            ->join('c.initiative', 'i')
            ->join('i.category', 'cat')
            ->where('cat.type = 0')
            ->andWhere('c.createdBy != :agent')
            ->setParameter('agent', $agent)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();

        if (empty($comments)) {
            $io->warning('No suitable comments found to react to.');
            return Command::SUCCESS;
        }

        /** @var Comment $target */
        $target = $comments[array_rand($comments)];

        $io->info(sprintf('Reacting to comment by %s on: %s', 
            $target->getCreatedBy()->getUsername(),
            $target->getInitiative()->getTitle()
        ));

        $reaction = $this->aiAssistant->decideReaction(
            "Comment on " . $target->getInitiative()->getTitle(),
            $target->getMessage(),
            $agent->getAiPersona()
        );

        if ($reaction === 'like') {
            $target->setLiked($target->getLiked() + 1);
            $io->success('Liked.');
        } elseif ($reaction === 'dislike') {
            $target->setDisliked($target->getDisliked() + 1);
            $io->success('Disliked.');
        } else {
            $io->info('Stayed neutral.');
        }

        $this->entityManager->flush();
        return Command::SUCCESS;
    }

    private function handleReply(SymfonyStyle $io, User $agent): int
    {
        // Find recent comments on global initiatives where this bot hasn't replied yet
        $qb = $this->entityManager->createQueryBuilder();
        $comments = $qb->select('c')
            ->from(Comment::class, 'c')
            ->join('c.initiative', 'i')
            ->join('i.category', 'cat')
            ->where('cat.type = 0')
            ->andWhere('c.createdBy != :agent')
            ->andWhere('c.parent IS NULL') // Reply to top-level comments for now
            ->setParameter('agent', $agent)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();

        if (empty($comments)) {
            $io->warning('No suitable comments found to reply to.');
            return Command::SUCCESS;
        }

        // Pick a random comment to reply to
        /** @var Comment $parentComment */
        $parentComment = $comments[array_rand($comments)];
        $targetInitiative = $parentComment->getInitiative();

        $io->info(sprintf('Replying to comment by %s on initiative: %s', 
            $parentComment->getCreatedBy()->getUsername(),
            $targetInitiative->getTitle()
        ));

        try {
            $replyText = $this->aiAssistant->generateReply(
                $targetInitiative->getTitle(),
                $parentComment->getMessage(),
                $agent->getAiPersona()
            );

            $reply = new Comment();
            $reply->setInitiative($targetInitiative);
            $reply->setParent($parentComment);
            $reply->setMessage($replyText);
            $reply->setCreatedBy($agent);
            $reply->setState(CommentEnum::STATE_OPEN);
            $reply->setLiked(0);
            $reply->setDisliked(0);
            $reply->setReported(0);
            $reply->setCreatedAt(new DateTime());
            $reply->setUpdatedAt(new DateTime());
            
            $this->entityManager->persist($reply);
            $this->entityManager->flush();

            $io->success('Reply posted successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to generate reply: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
