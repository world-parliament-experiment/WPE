<?php

namespace App\Command;

use App\Entity\Comment;
use App\Entity\Initiative;
use App\Entity\User;
use App\Entity\Category;
use App\Entity\Voting;
use App\Entity\Vote;
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
    private $voteEncryptionService;

    public function __construct(
        AiAssistantService $aiAssistant,
        EntityManagerInterface $entityManager,
        \App\Service\VoteEncryptionService $voteEncryptionService
    ) {
        parent::__construct();
        $this->aiAssistant = $aiAssistant;
        $this->entityManager = $entityManager;
        $this->voteEncryptionService = $voteEncryptionService;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Triggers AI agents to perform autonomous actions (drafting, commenting, replying, reacting, voting, or counter-proposals)')
            ->addArgument('action', InputArgument::REQUIRED, 'Action to perform: "draft", "comment", "reply", "react", "vote", or "counter-proposal"')
            ->addOption('persona', 'p', InputOption::VALUE_REQUIRED, 'Target a specific persona (if omitted, a random AI agent is chosen)')
            ->addOption('topic', 't', InputOption::VALUE_REQUIRED, 'Topic for drafting (if omitted, the agent will invent one)')
            ->addOption('category', 'c', InputOption::VALUE_REQUIRED, 'Category name for drafting (if omitted, a random Type 0 category is chosen)')
            ->addOption('initiative', 'i', InputOption::VALUE_REQUIRED, 'Target specific initiative ID for counter-proposals or comments')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force submission even if flagged as semantic duplicate')
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
        } elseif ($action === 'vote') {
            return $this->handleVote($io, $agent);
        } elseif ($action === 'counter-proposal' || $action === 'counter' || $action === 'draft-response') {
            return $this->handleCounterProposal($input, $io, $agent);
        }

        $io->error('Invalid action. Use "draft", "comment", "reply", "react", "vote", or "counter-proposal".');
        return Command::FAILURE;
    }

    private function handleDraft(InputInterface $input, SymfonyStyle $io, User $agent): int
    {
        $topic = $input->getOption('topic');
        $categoryName = $input->getOption('category');
        $force = (bool)$input->getOption('force');
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

        if ($category->getType() !== 0) {
            $io->error('AI agents can only draft in Type 0 categories.');
            return Command::FAILURE;
        }

        // Fetch existing non-deleted proposals in this category for context and duplicate checking
        $qb = $this->entityManager->createQueryBuilder();
        $existingInitiatives = $qb->select('i.id, i.title, i.description')
            ->from(Initiative::class, 'i')
            ->where('i.category = :category')
            ->andWhere('i.state != :deletedState')
            ->setParameter('category', $category)
            ->setParameter('deletedState', InitiativeEnum::STATE_DELETED)
            ->orderBy('i.id', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getArrayResult();

        $existingTitles = array_column($existingInitiatives, 'title');

        $draft = null;

        // 2. Resolve Topic & Draft Proposal
        if ($topic) {
            $io->info(sprintf('Drafting proposal about "%s" in category "%s"...', $topic, $category->getName()));
            try {
                $candidateDraft = $this->aiAssistant->draftFullInitiative($topic, $agent->getAiPersona());

                if (!empty($existingInitiatives) && !$force) {
                    $io->info('Checking for semantic duplicates against existing proposals...');
                    $dupCheck = $this->aiAssistant->checkSemanticDuplicate(
                        $candidateDraft['title'],
                        $candidateDraft['description'],
                        $existingInitiatives,
                        $agent->getAiPersona(),
                        $category->getName()
                    );

                    if ($dupCheck['is_duplicate']) {
                        $io->error(sprintf(
                            'Proposal not submitted: Draft "%s" is a semantic duplicate of existing proposal #%d: %s (Use --force to override)',
                            $candidateDraft['title'],
                            $dupCheck['duplicate_of_id'] ?? 0,
                            $dupCheck['reason'] ?: 'Same meaning behind the proposal'
                        ));
                        return Command::FAILURE;
                    }
                }

                $draft = $candidateDraft;
            } catch (\Exception $e) {
                $io->error('Failed to draft initiative: ' . $e->getMessage());
                return Command::FAILURE;
            }
        } else {
            $maxAttempts = 3;
            $rejectedTopics = [];

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                $io->info(sprintf('Autonomous drafting attempt %d/%d for category "%s"...', $attempt, $maxAttempts, $category->getName()));

                try {
                    $allExcludedTitles = array_merge($existingTitles, $rejectedTopics);
                    $inventedTopic = $this->aiAssistant->inventTopic($agent->getAiPersona(), $category->getName(), $allExcludedTitles);
                    $io->info(sprintf('Agent invented topic: "%s"', $inventedTopic));

                    $io->info(sprintf('Drafting proposal about "%s"...', $inventedTopic));
                    $candidateDraft = $this->aiAssistant->draftFullInitiative($inventedTopic, $agent->getAiPersona());

                    if (!empty($existingInitiatives) && !$force) {
                        $io->info('Checking for semantic duplicates against existing proposals...');
                        $dupCheck = $this->aiAssistant->checkSemanticDuplicate(
                            $candidateDraft['title'],
                            $candidateDraft['description'],
                            $existingInitiatives,
                            $agent->getAiPersona(),
                            $category->getName()
                        );

                        if ($dupCheck['is_duplicate']) {
                            $io->warning(sprintf(
                                'Draft "%s" is a semantic duplicate of proposal #%d: %s. Retrying with a new topic...',
                                $candidateDraft['title'],
                                $dupCheck['duplicate_of_id'] ?? 0,
                                $dupCheck['reason'] ?: 'Same meaning behind the proposal'
                            ));
                            $rejectedTopics[] = $inventedTopic;
                            $rejectedTopics[] = $candidateDraft['title'];
                            continue;
                        }
                    }

                    $draft = $candidateDraft;
                    break;
                } catch (\Exception $e) {
                    $io->error('Failed during drafting attempt: ' . $e->getMessage());
                    return Command::FAILURE;
                }
            }

            if (!$draft) {
                $io->error(sprintf(
                    'Could not generate a unique proposal after %d attempts in category "%s". Aborting submission to prevent duplicate.',
                    $maxAttempts,
                    $category->getName()
                ));
                return Command::FAILURE;
            }
        }

        try {
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

    private function handleVote(SymfonyStyle $io, User $agent): int
    {
        // Find active global initiatives
        $initiatives = $this->entityManager->getRepository(Initiative::class)
            ->createQueryBuilder('i')
            ->join('i.category', 'c')
            ->where('c.type = 0')
            ->andWhere('i.state = :state')
            ->setParameter('state', InitiativeEnum::STATE_ACTIVE)
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();

        if (empty($initiatives)) {
            $io->warning('No active global initiatives found to vote on.');
            return Command::SUCCESS;
        }

        $ownProposals = [];
        $otherProposals = [];
        $ownOngoingVotes = [];
        $otherOngoingVotes = [];

        foreach ($initiatives as $initiative) {
            $ongoingVoting = null;
            $proposalVoting = null;

            if (($cv = $initiative->getCurrentVoting()) instanceof Voting) {
                if ($cv->getState() === VotingEnum::STATE_OPEN || ($cv->getState() === VotingEnum::STATE_WAITING && ($cv->getStartdate() === null || $cv->getStartdate() <= new DateTime()))) {
                    $ongoingVoting = $cv;
                }
            }

            if (!$ongoingVoting && ($fv = $initiative->getFutureVoting()) instanceof Voting) {
                if ($fv->getState() === VotingEnum::STATE_OPEN || ($fv->getState() === VotingEnum::STATE_WAITING && ($fv->getStartdate() === null || $fv->getStartdate() <= new DateTime()))) {
                    $proposalVoting = $fv;
                }
            }

            $isOwn = $this->isOwnInitiative($initiative, $agent);

            if ($proposalVoting) {
                $existingVote = $this->entityManager->getRepository(Vote::class)->findOneBy([
                    'user' => $agent,
                    'voting' => $proposalVoting
                ]);

                if (!$existingVote) {
                    if ($proposalVoting->getState() === VotingEnum::STATE_WAITING) {
                        $proposalVoting->setState(VotingEnum::STATE_OPEN);
                        $this->entityManager->persist($proposalVoting);
                    }

                    if ($isOwn) {
                        $ownProposals[] = ['initiative' => $initiative, 'voting' => $proposalVoting];
                    } else {
                        $otherProposals[] = ['initiative' => $initiative, 'voting' => $proposalVoting];
                    }
                }
            } elseif ($ongoingVoting) {
                $existingVote = $this->entityManager->getRepository(Vote::class)->findOneBy([
                    'user' => $agent,
                    'voting' => $ongoingVoting
                ]);

                if (!$existingVote) {
                    if ($ongoingVoting->getState() === VotingEnum::STATE_WAITING) {
                        $ongoingVoting->setState(VotingEnum::STATE_OPEN);
                        $this->entityManager->persist($ongoingVoting);
                    }

                    if ($isOwn) {
                        $ownOngoingVotes[] = ['initiative' => $initiative, 'voting' => $ongoingVoting];
                    } else {
                        $otherOngoingVotes[] = ['initiative' => $initiative, 'voting' => $ongoingVoting];
                    }
                }
            }
        }

        $votedProposal = false;
        $votedOngoing = false;

        // 1. Vote on ONE proposal: Own proposals first, then others
        if (!empty($ownProposals)) {
            usort($ownProposals, function ($a, $b) {
                $timeA = $a['initiative']->getCreatedAt() ? $a['initiative']->getCreatedAt()->getTimestamp() : 0;
                $timeB = $b['initiative']->getCreatedAt() ? $b['initiative']->getCreatedAt()->getTimestamp() : 0;
                return $timeB <=> $timeA;
            });

            $target = $ownProposals[0];
            /** @var Initiative $initiative */
            $initiative = $target['initiative'];
            /** @var Voting $voting */
            $voting = $target['voting'];

            $io->info(sprintf('Voting on own proposal: %s', $initiative->getTitle()));
            $this->castVote($agent, $voting, 1);
            $io->success('Voted: 1 (Supported own proposal)');
            $votedProposal = true;
        } elseif (!empty($otherProposals)) {
            shuffle($otherProposals);

            foreach ($otherProposals as $target) {
                /** @var Initiative $initiative */
                $initiative = $target['initiative'];
                /** @var Voting $voting */
                $voting = $target['voting'];

                $io->info(sprintf('Evaluating proposal: %s', $initiative->getTitle()));

                $value = $this->aiAssistant->decideVote(
                    $initiative->getTitle(),
                    $initiative->getDescription(),
                    $agent->getAiPersona(),
                    false
                );

                if ($value === 1) {
                    $this->castVote($agent, $voting, 1);
                    $io->success('Voted: 1 (Supported proposal)');
                    $votedProposal = true;
                    break;
                } else {
                    $io->info('Decided not to support.');
                }
            }

            if (!$votedProposal) {
                $io->info('No candidate proposals were supported in this round.');
            }
        } else {
            $io->info('No proposals available for this agent to vote on.');
        }

        // 2. Vote on ONE ongoing vote: Own ongoing votes first, then others (if available)
        if (!empty($ownOngoingVotes)) {
            usort($ownOngoingVotes, function ($a, $b) {
                $timeA = $a['initiative']->getCreatedAt() ? $a['initiative']->getCreatedAt()->getTimestamp() : 0;
                $timeB = $b['initiative']->getCreatedAt() ? $b['initiative']->getCreatedAt()->getTimestamp() : 0;
                return $timeB <=> $timeA;
            });

            $target = $ownOngoingVotes[0];
            /** @var Initiative $initiative */
            $initiative = $target['initiative'];
            /** @var Voting $voting */
            $voting = $target['voting'];

            $io->info(sprintf('Voting on own ongoing vote: %s', $initiative->getTitle()));
            $this->castVote($agent, $voting, 1);
            $io->success('Voted: 1 (Voted YES on own ongoing vote)');
            $votedOngoing = true;
        } elseif (!empty($otherOngoingVotes)) {
            shuffle($otherOngoingVotes);

            $target = $otherOngoingVotes[0];
            /** @var Initiative $initiative */
            $initiative = $target['initiative'];
            /** @var Voting $voting */
            $voting = $target['voting'];

            $io->info(sprintf('Voting on official ongoing vote: %s', $initiative->getTitle()));

            $value = $this->aiAssistant->decideVote(
                $initiative->getTitle(),
                $initiative->getDescription(),
                $agent->getAiPersona(),
                true
            );

            $this->castVote($agent, $voting, $value);
            $io->success(sprintf('Voted: %d', $value));
            $votedOngoing = true;
        } else {
            $io->info('No ongoing votes available for this agent to vote on.');
        }

        if (!$votedProposal && !$votedOngoing) {
            $io->info('No votes cast by this agent in this round.');
        }

        return Command::SUCCESS;
    }

    private function isOwnInitiative(Initiative $initiative, User $agent): bool
    {
        $creator = $initiative->getCreatedBy();
        if (!$creator) {
            return false;
        }

        if ($creator === $agent) {
            return true;
        }

        if ($agent->getId() !== null && $creator->getId() !== null) {
            return $creator->getId() === $agent->getId();
        }

        return false;
    }

    private function castVote(User $user, Voting $voting, int $value): void
    {
        $vote = new Vote();
        $vote->setUser($user);
        $vote->setVoting($voting);
        $vote->setValue($this->voteEncryptionService->encrypt($value));
        $vote->setVotedAt(new DateTime());

        $this->entityManager->persist($vote);
        $this->entityManager->flush();
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

    private function handleCounterProposal(InputInterface $input, SymfonyStyle $io, User $agent): int
    {
        $initiativeId = $input->getOption('initiative');
        /** @var Initiative|null $target */
        $target = null;

        if ($initiativeId) {
            $target = $this->entityManager->getRepository(Initiative::class)->find($initiativeId);
            if (!$target) {
                $io->error(sprintf('Initiative with ID "%s" not found.', $initiativeId));
                return Command::FAILURE;
            }
        } else {
            // Find candidate active global initiatives created by others or any active initiative
            $qb = $this->entityManager->createQueryBuilder();
            $initiatives = $qb->select('i')
                ->from(Initiative::class, 'i')
                ->join('i.category', 'c')
                ->where('c.type = 0')
                ->andWhere('i.state = :state')
                ->andWhere('i.createdBy != :agent')
                ->setParameter('state', InitiativeEnum::STATE_ACTIVE)
                ->setParameter('agent', $agent)
                ->orderBy('i.createdAt', 'DESC')
                ->setMaxResults(20)
                ->getQuery()
                ->getResult();

            if (empty($initiatives)) {
                // Fallback: any active global initiative
                $initiatives = $this->entityManager->createQueryBuilder()
                    ->select('i')
                    ->from(Initiative::class, 'i')
                    ->join('i.category', 'c')
                    ->where('c.type = 0')
                    ->andWhere('i.state = :state')
                    ->setParameter('state', InitiativeEnum::STATE_ACTIVE)
                    ->orderBy('i.createdAt', 'DESC')
                    ->setMaxResults(20)
                    ->getQuery()
                    ->getResult();
            }

            if (empty($initiatives)) {
                $io->warning('No active global initiatives found to create a counter-proposal for.');
                return Command::SUCCESS;
            }

            $target = $initiatives[array_rand($initiatives)];
        }

        $io->info(sprintf('Drafting counter-proposal in response to initiative: %s (ID: %d)', $target->getTitle(), $target->getId()));

        try {
            // Step 1: Agent generates a comment/critique on the target initiative
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

            $io->info(sprintf('Comment posted on target initiative (ID: %d). Now drafting new proposal...', $target->getId()));

            // Step 2: Agent drafts a new proposal spawned off this comment
            $draft = $this->aiAssistant->draftCounterProposal(
                $target->getTitle(),
                $target->getDescription(),
                $commentText,
                $agent->getAiPersona()
            );

            // Check for semantic duplicates against existing proposals in category (excluding target)
            $qb = $this->entityManager->createQueryBuilder();
            $existingInitiatives = $qb->select('i.id, i.title, i.description')
                ->from(Initiative::class, 'i')
                ->where('i.category = :category')
                ->andWhere('i.state != :deletedState')
                ->andWhere('i.id != :targetId')
                ->setParameter('category', $target->getCategory())
                ->setParameter('deletedState', InitiativeEnum::STATE_DELETED)
                ->setParameter('targetId', $target->getId())
                ->orderBy('i.id', 'DESC')
                ->setMaxResults(50)
                ->getQuery()
                ->getArrayResult();

            if (!empty($existingInitiatives) && !$input->getOption('force')) {
                $io->info('Checking for semantic duplicates against existing proposals...');
                $dupCheck = $this->aiAssistant->checkSemanticDuplicate(
                    $draft['title'],
                    $draft['description'],
                    $existingInitiatives,
                    $agent->getAiPersona(),
                    $target->getCategory() ? $target->getCategory()->getName() : ''
                );

                if ($dupCheck['is_duplicate']) {
                    $io->error(sprintf(
                        'Counter-proposal not submitted: Draft "%s" is a semantic duplicate of existing proposal #%d: %s (Use --force to override)',
                        $draft['title'],
                        $dupCheck['duplicate_of_id'] ?? 0,
                        $dupCheck['reason'] ?: 'Same meaning behind the proposal'
                    ));
                    return Command::FAILURE;
                }
            }

            $slugger = new AsciiSlugger();
            $initiative = new Initiative();
            $voting = new Voting();

            $now = new DateTime();
            $initiative->setCategory($target->getCategory());
            $initiative->setTitle($draft['title']);

            // Add reference to original proposal in description if not present
            $description = $draft['description'];
            if (!str_contains($description, 'Original Proposal')) {
                $refHtml = sprintf(
                    '<p><em>Response to proposal <a href="/en/proposal/%s">%s</a> (ID: %d).</em></p><hr/>',
                    $target->getSlug() ?? $target->getId(),
                    htmlspecialchars($target->getTitle()),
                    $target->getId()
                );
                $description = $refHtml . $description;
            }
            $initiative->setDescription($description);
            $initiative->setCreatedBy($agent);
            $initiative->setDuration("7");
            $initiative->setPublishedAt($now);
            $initiative->setCreatedAt($now);
            $initiative->setUpdatedAt($now);
            $initiative->setSlug($slugger);
            $initiative->setType(InitiativeEnum::TYPE_FUTURE);
            $initiative->setState(InitiativeEnum::STATE_ACTIVE);

            $voting->setStartdate($now);
            $enddate = (clone $now)->modify('+6 months');
            $voting->setEnddate($enddate);
            $voting->setState(VotingEnum::STATE_WAITING);
            $voting->setType(VotingEnum::TYPE_FUTURE);
            $voting->setInitiative($initiative);

            $this->entityManager->persist($initiative);
            $this->entityManager->persist($voting);
            $this->entityManager->flush();

            // Step 3: Update original comment to link to the newly spawned proposal
            $updatedCommentMessage = $commentText . sprintf(
                "\n\n*(I have drafted a new proposal in response: Proposal #%d - %s)*",
                $initiative->getId(),
                $initiative->getTitle()
            );
            $comment->setMessage($updatedCommentMessage);
            $this->entityManager->flush();

            $io->success(sprintf(
                'New counter-proposal created: "%s" (ID: %d), spawned from comment on proposal "%s" (ID: %d)',
                $initiative->getTitle(),
                $initiative->getId(),
                $target->getTitle(),
                $target->getId()
            ));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to create counter-proposal: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
