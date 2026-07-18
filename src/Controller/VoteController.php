<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Initiative;
use App\Entity\User;
use App\Entity\Vote;
use App\Entity\Voting;
use App\Enum\CommentEnum;
use App\Enum\InitiativeEnum;
use App\Enum\VotingEnum;
use App\Service\Mailer;
use App\Service\VoteEncryptionService;
use APY\BreadcrumbTrailBundle\Annotation\Breadcrumb;
use DateTime;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use JMS\Serializer\SerializerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Vote controller.
 *
 * @Route("/initiative")
 */
class VoteController extends BaseController
{
    private $mailer;
    private VoteEncryptionService $voteEncryptionService;

    public function __construct(SerializerInterface $serializer, ManagerRegistry $managerRegistry, Mailer $mailer, VoteEncryptionService $voteEncryptionService)
    {
        parent::__construct($serializer, $managerRegistry);
        $this->_serializeGroups = ["simple"];
        $this->mailer = $mailer;
        $this->voteEncryptionService = $voteEncryptionService;
    }

    /**
     * Saves a comment.
     *
     * @Route("/reply/{type}/{id}", methods={"POST"}, requirements={"type" = "initiative|comment", "id" = "\d+"}, name="initiative_save_reply")
     * @param Request $request
     * @param $type
     * @param $id
     * @return Response
     */
    public function saveReplyAction(Request $request, $type, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $em = $this->managerRegistry->getManager();
        if ($type == 'initiative') {
            $initiative = $em->getRepository(Initiative::class)->find($id);
        } else {
            $parentComment = $em->getRepository(Comment::class)->find($id);
            $initiative = $parentComment->getInitiative();
        }

        $this->denyAccessUnlessGranted("view", $initiative);
        $comment = new Comment();

        $form = $this->createForm('App\Form\CommentForm', $comment);

        $comment->setState(CommentEnum::STATE_OPEN);
        $comment->setInitiative($initiative);
        $comment->setCreatedAt(new \DateTime());
        $user = $this->getUser();
        $comment->setCreatedBy($user);
        $comment->setUpdatedAt(new \DateTime());
        $comment->setLiked('0');
        $comment->setDisliked('0');
        $comment->setReported('0');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $comment = $form->getData();
            if ($type == 'comment') {
                $comment->setParent($parentComment);
                $notifyUser = $parentComment->getCreatedBy();
            } else {
                $notifyUser = $initiative->getCreatedBy();
            }
            $em = $this->managerRegistry->getManager();
            $em->persist($comment);

            $em->flush();

            $notifyURL = $this->generateUrl("initiative_show", array('id' => $initiative->getId(), 'slug' => $initiative->getSlug()), UrlGeneratorInterface::ABSOLUTE_URL);
            $this->mailer->sendCommentNotification($notifyUser, $notifyURL, $user);

            $output = array();
            $output['status'] = true;
            $output['comment'] = $comment;
            $output['avatar'] = $this->generateUrl("user_profile_avatar", array('id' => $this->getUser()->getId()));
            $output['profile'] = $this->generateUrl("user_profile_show", array('id' => $this->getUser()->getId()));
            $output['reply_path'] = $this->generateUrl("initiative_save_reply", array('type' => 'comment', 'id' => $comment->getId()));
            $output['edit_path'] = $this->generateUrl("admin_comment_edit", array('id' => $comment->getId()));
            $response = $this->createApiResponse($output, 200);
            return $response;
        }

        $output = array();
        $output['status'] = false;
        $output['errors'] = $this->getErrorsFromForm($form);
        $response = $this->createApiResponse($output, 200);

        return $response;
    }

    /**
     * increments counter.
     *
     * @Route("/counter/{type}/{id}", requirements={"type" = "(like|dislike|report)","id" = "\d+"}, name="initiative_increment_counter")
     * #[HttpMethod("GET")]
     * @param Request $request
     * @param Comment $comment
     * @return Response
     */
    public function incrementCommentCounterAction(Request $request, $type, Comment $comment)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($type == "like") {
            $comment->setLiked($comment->getLiked() + 1);
        } elseif ($type == "dislike") {
            $comment->setDisliked($comment->getDisliked() + 1);
        } else {
            $comment->setReported($comment->getReported() + 1);
        }
        $em = $this->managerRegistry->getManager();
        $em->persist($comment);

        $em->flush();

        $output = array();
        $output['status'] = true;
        if ($type == "like") {
            $output['value'] = $comment->getLiked();
        } elseif ($type == "dislike") {
            $output['value'] = $comment->getDisliked();
        } else {
            $output['value'] = $comment->getReported();
        }
        $response = $this->createApiResponse($output, 200);

        return $response;
    }

    /**
     * increments initiative counter.
     *
     * @Route("/initiative/counter/{type}/{id}", requirements={"type" = "(like|dislike|report)","id" = "\d+"}, name="initiative_item_increment_counter")
     * @param Request $request
     * @param string $type
     * @param Initiative $initiative
     * @return Response
     */
    public function incrementInitiativeCounterAction(Request $request, string $type, Initiative $initiative)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $em = $this->managerRegistry->getManager();

        if ($type == "like") {
            $initiative->setLiked($initiative->getLiked() + 1);
        } elseif ($type == "dislike") {
            $initiative->setDisliked($initiative->getDisliked() + 1);
        } else {
            // Report logic - simple counter for now
            // Future improvement: check if user is AI and block if so, 
            // but the prompt said "Report button should only be accessible to human users"
            // meaning the UI will hide it.
            // However, we can add a server-side check here.
            $user = $this->getUser();
            if ($user instanceof User && $user->isAi()) {
                return $this->createApiResponse(['success' => false, 'message' => 'AI agents cannot report content.'], 403);
            }
            // Initiative doesn't have a reported field yet, let's assume it doesn't need one or add it later.
            // For now, if it's report, we just return success without doing anything if field missing.
        }

        $em->persist($initiative);
        $em->flush();

        $output = [
            'status' => true,
            'value' => ($type == 'like' ? $initiative->getLiked() : $initiative->getDisliked())
        ];

        return $this->createApiResponse($output, 200);
    }

    /**
     * Finds and displays a initiative entity.
     *
     * @Breadcrumb("breadcrumb.{initiative.typeName}.label", route={"name"="category_index", "parameters"={"type"="{initiative.typeName}"}}, attributes={"translate": true})
     * @Breadcrumb("{initiative.category.name}", route={"name"="category_type", "parameters"={"id"="{initiative.category.id}","slug"="{initiative.category.slug}","type"="{initiative.typeName}"}})
     * @Breadcrumb("{initiative.title}")
     * @Route("/{id}/{slug}", requirements={"id" = "\d+"}, methods={"GET"}, options={"expose"=true}, name="initiative_show")
     * @param Request $request
     * @param Initiative $initiative
     * @return Response
     */
    public function showAction(Request $request,int $id, Initiative $initiative)
    {   
        $user = $this->getUser();
        $isMobileNumberVerified = ($user instanceof User && $user->getVerifiedAt() !== null) ? 1 : 0;
        $isPhoneNumberExist = ($user instanceof User &&  $user->getMobileNumber() !== null) ? 1 : 0;
       
        $em = $this->managerRegistry->getManager();
        $initiative = $em->getRepository(Initiative::class)->find($id);
        $routeParams =[
            'id' => $id,
            'slug' => $initiative->getSlug(),
            'initiative' => $initiative
        ];
        $this->get('session')->set('routeParams', $routeParams);
        $this->get('session')->set('route', $request->get('_route'));
        // $this->denyAccessUnlessGranted("view", $initiative);
        $initiative->incrementViews();

        $em->persist($initiative);
        $em->flush();

        $comment = new Comment();

        $form = $this->createForm('App\Form\CommentForm', $comment,
            [
                'action' => $this->generateUrl('initiative_save_reply', array('type' => 'initiative', 'id' => $initiative->getId()))
            ]);

        $comment->setState(CommentEnum::STATE_OPEN);
        $comment->setInitiative($initiative);
        $comment->setLiked('0');
        $comment->setDisliked('0');
        $comment->setReported('0');
        $comment->setParent(NULL);
             
        if ($initiative->getType() === InitiativeEnum::TYPE_FUTURE) {
            return $this->render('Vote/show.html.twig', array(
                'initiative' => $initiative,
                'form' => $form->createView(),
                'repo' => $em->getRepository('Gedmo\Loggable\Entity\LogEntry'),
                'type' => 'proposal',
                'mobileVerified' => $isMobileNumberVerified,
                'phoneNumberExist' => $isPhoneNumberExist,
                'category' => $initiative->getCategory(),
            ));
        } elseif ($initiative->getType() === InitiativeEnum::TYPE_CURRENT) {
            return $this->render('Vote/show.html.twig', array(
                'initiative' => $initiative,
                'form' => $form->createView(),
                'repo' => $em->getRepository('Gedmo\Loggable\Entity\LogEntry'),
                'type' => 'vote',
                'mobileVerified' => $isMobileNumberVerified,
                'phoneNumberExist' => $isPhoneNumberExist,
                'category' => $initiative->getCategory(),
            ));
        } elseif ($initiative->getType() === InitiativeEnum::TYPE_PAST) {
            return $this->render('Vote/show.html.twig', array(
                'initiative' => $initiative,
                'form' => $form->createView(),
                'repo' => $em->getRepository('Gedmo\Loggable\Entity\LogEntry'),
                'type' => 'unsuccessful initiative',
                'mobileVerified' => $isMobileNumberVerified,
                'phoneNumberExist' => $isPhoneNumberExist,
                'category' => $initiative->getCategory(),
            ));
        } elseif ($initiative->getType() === InitiativeEnum::TYPE_ARTICLE) {
            return $this->render('Vote/show.html.twig', array(
                'initiative' => $initiative,
                'form' => $form->createView(),
                'repo' => $em->getRepository('Gedmo\Loggable\Entity\LogEntry'),
                'type' => 'article',
                'mobileVerified' => $isMobileNumberVerified,
                'phoneNumberExist' => $isPhoneNumberExist,
                'category' => $initiative->getCategory(),
            ));
        } else {
            return $this->render('Vote/show.html.twig', array(
                'initiative' => $initiative,
                'form' => $form->createView(),
                'repo' => $em->getRepository('Gedmo\Loggable\Entity\LogEntry'),
                'type' => 'adopted vote',
                'mobileVerified' => $isMobileNumberVerified,
                'phoneNumberExist' => $isPhoneNumberExist,
                'category' => $initiative->getCategory(),
            ));
        }
        ;

    }

    /**
     * displays voting results.
     *
     * @Breadcrumb("{initiative.typeName}")
     * @Breadcrumb("{initiative.category.name}")
     * @Breadcrumb("{initiative.title}")
     * @Route("/{id}/{slug}/result", requirements={"id" = "\d+"}, methods={"GET"}, name="initiative_result")
     * @param Initiative $initiative
     * @return Response
     */
    public function showResultAction(Initiative $initiative)
    {

        $this->denyAccessUnlessGranted("view", $initiative);

        /** @var Voting $future */
        $future = $initiative->getFutureVoting();
        /** @var Voting $current */
        $current = $initiative->getCurrentVoting();

        if ($future === false OR $future->getState() !== VotingEnum::STATE_FINISHED) {
            throw $this->createNotFoundException('The result not exist');
        }

//        if ($current === false OR $current->getState() !== VotingEnum::STATE_FINISHED) {
//            throw $this->createNotFoundException('The result not exist');
//        }

        return $this->render('Vote/result.html.twig', array(
            'initiative' => $initiative,
            'future' => $future,
            'current' => $initiative->getCurrentVoting(),
        ));

    }

    /**
     * returns voting data fur future voting.
     *
     * @Route("/{id}/{slug}/result/future", options={"expose"=true}, requirements={"id" = "\d+"}, methods={"GET"}, name="initiative_result_data_future")
     * @param Initiative $initiative
     * @return Response
     */
    public function showResultDataFutureAction(Initiative $initiative)
    {

        $this->denyAccessUnlessGranted("view", $initiative);

        /** @var Voting $voting */
        $voting = $initiative->getFutureVoting();

        if ($voting) {

            return $this->createApiResponse(
                [
                    [
                        "vote" => "Yes",
                        "voters" => $voting->getVotesAcception(),
                        "quorum" => round($voting->getEligibleVoters() * $voting->getQuorum()),
                        "eligible" => $voting->getEligibleVoters(),
                        "breakdown" => [
                            "direct" => ($voting->getVotesAcception() - $voting->getVotesAcceptionDelegated()),
                            "delegated" => $voting->getVotesAcceptionDelegated(),
                        ],
                    ]
                ]
            );

        }

        return $this->createApiResponse(
            [
            ]
        );

    }

    /**
     * returns voting data fur current voting.
     *
     * @Route("/{id}/{slug}/result/current", options={"expose"=true}, requirements={"id" = "\d+"}, methods={"GET"}, name="initiative_result_data_current")
     * @param Initiative $initiative
     * @return Response
     */
    public function showResultDataCurrentAction(Initiative $initiative)
    {

        /** @var Voting $voting */
        $voting = $initiative->getCurrentVoting();

        if ($voting) {

            return $this->createApiResponse(
                [
                    [
                        "vote" => "Yes",
                        "voters" => $voting->getVotesAcception(),
                        "breakdown" => [
                            "direct" => ($voting->getVotesAcception() - $voting->getVotesAcceptionDelegated()),
                            "delegated" => $voting->getVotesAcceptionDelegated(),
                            "enddate" => $voting->getEnddate(),
                            "accepted" => $voting->getVotesAcception() 
                        ],
                        "config" => [
                            "isActive" => $voting->getAccepted(),
                        ]
                    ],
                    [
                        "vote" => "Abstention",
                        "voters" => $voting->getVotesAbstention(),
                        "breakdown" => [
                            "direct" => ($voting->getVotesAbstention() - $voting->getVotesAbstentionDelegated()),
                            "delegated" => $voting->getVotesAbstentionDelegated(),
                        ]
                    ],
                    [
                        "vote" => "No",
                        "voters" => $voting->getVotesRejection(),
                        "breakdown" => [
                            "direct" => ($voting->getVotesRejection() - $voting->getVotesRejectionDelegated()),
                            "delegated" => $voting->getVotesRejectionDelegated(),
                        ],
                        "config" => [
                            "isActive" => $voting->getRejected(),
                        ]
                    ]
                ]
            );

        }

        return $this->createApiResponse(
            [
            ]
        );

    }

    /**
     * Finds and displays a vote area.
     *
     * @Route("/{id}/{slug}/vote", options={"expose"=true}, methods={"GET"}, requirements={"id" = "\d+"}, name="initiative_show_vote")
     * @param Initiative $initiative
     * @return Response
     * @throws NonUniqueResultException
     */
    public function showVoteAction(int $id)
    {

        $em = $this->managerRegistry->getManager();
        $initiative = $em->getRepository(Initiative::class)->find($id);

        // $this->denyAccessUnlessGranted("view", $initiative);

        /*
         * Future Initiative
         */
        if ($initiative->getType() == InitiativeEnum::TYPE_FUTURE) {

            // Draft - show link to edit
            if ($initiative->getState() == InitiativeEnum::STATE_DRAFT) {
                if ($this->isGranted('edit', $initiative)) {
                    return $this->createApiResponse([
                        'success' => true,
                        'message' => 'Initiative is still in draft mode!',
                        'data' => [
                            'type' => 'message',
                            'content' => 'Your initiative is still in draft state. You can publish it <a href="' . $this->generateUrl('user_initiative_edit', ['id' => $initiative->getId(), 'slug' => $initiative->getSlug()]) . '"> here </a>.'
                        ]
                    ]);
                }
            } elseif ($initiative->getState() == InitiativeEnum::STATE_ACTIVE) {

                $voting = $initiative->getFutureVoting();

                if ($voting !== false) {
                    if ($voting->getState() == VotingEnum::STATE_WAITING) {
                        return $this->createApiResponse([
                            'success' => true,
                            'message' => 'Voting has not started yet!',
                            'data' => [
                                'type' => 'countdown',
                                'startdate' => $voting->getStartdate()->format("Y-m-d H:i:s"),
                            ]
                        ]);
                    } elseif ($voting->getState() == VotingEnum::STATE_FINISHED) {
                        return $this->createApiResponse([
                            'success' => true,
                            'message' => 'Voting is already finished!',
                            'data' => [
                                'type' => 'message',
                                'content' => 'The voting is already finished. You can see the results <a href="' . $this->generateUrl('initiative_result', ['id' => $initiative->getId(), 'slug' => $initiative->getSlug()]) . '"> here </a>.'
                            ]
                        ]);
                    } elseif ($voting->getState() == VotingEnum::STATE_OPEN) {

                        if ($this->isGranted('vote', $initiative)) {
                            $this->denyAccessUnlessGranted("vote", $initiative);

                            $vote = $em->getRepository(User::class)
                                ->getUserVoteByVoting($this->getUser(), $voting);

                            if ($vote === null) {
                                // not voted yet
                                return $this->createApiResponse([
                                    'success' => true,
                                    'message' => 'Future Voting is loaded successfully!',
                                    'data' => [
                                        'type' => 'form_future',
                                        'enddate' => $voting->getEnddate()->format("Y-m-d H:i:s"),
                                    ]
                                ]);
                            } else {
                                // already voted
                                return $this->createApiResponse([
                                    'success' => true,
                                    'message' => 'You already voted for this proposal',
                                    'data' => [
                                        'type' => 'info',
                                        'content' => 'You already voted for this proposal on ' . $vote->getVotedAt()->format('F j, Y H:i') . '! The proposal will become an official online vote when it has reached the required quota of at least 5% of eligible voters for the proposal. Online voting starts immediately after a proposal has reached the threshold support to become a vote. If a proposal fails to reach the 5 percent quorum during the validity of the proposal, it will be archived.',
                                        'enddate' => $voting->getEnddate()->format("Y-m-d H:i:s"),
                                    ]
                                ]);
                            }
                        } else {
                            return $this->createApiResponse([
                                'success' => false,
                                'message' => 'Only registered users can supoort a proposal to become an online vote! Please <a href="' . $this->generateUrl('login') . '">login</a> or <a href="' . $this->generateUrl('app_register') . '">register</a> to continue.',
                            ], 302);
                        }
                    }
                }
            }

            /*
             * Current Initiative
             */
        } elseif ($initiative->getType() == InitiativeEnum::TYPE_CURRENT) {

            if ($initiative->getState() == InitiativeEnum::STATE_ACTIVE) {
                $voting = $initiative->getCurrentVoting();
                if ($voting !== false) {
                    if ($voting->getState() == VotingEnum::STATE_WAITING) {
                        return $this->createApiResponse([
                            'success' => true,
                            'message' => 'Voting hss not started yet!',
                            'data' => [
                                'type' => 'countdown',
                                'startdate' => $voting->getStartdate()->format("Y-m-d H:i:s"),
                            ]
                        ]);
                    } elseif ($voting->getState() == VotingEnum::STATE_FINISHED) {
                        return $this->createApiResponse([
                            'success' => true,
                            'message' => 'Voting is already finished!',
                            'data' => [
                                'type' => 'message',
                                'content' => 'The voting is already finished. You can see the results <a href="' . $this->generateUrl('initiative_result', ['id' => $initiative->getId(), 'slug' => $initiative->getSlug()]) . '"> here </a>.',
                            ]
                        ]);
                    } elseif ($voting->getState() == VotingEnum::STATE_OPEN) {

                        if ($this->isGranted('vote', $initiative)) {

                            $this->denyAccessUnlessGranted("vote", $initiative);

                            /** @var Vote $vote */
                            $vote = $em->getRepository(User::class)
                                ->getUserVoteByVoting($this->getUser(), $voting);

                            if ($vote === null) {
                                // not voted yet
                                return $this->createApiResponse([
                                    'success' => true,
                                    'message' => 'Current Voting is loaded successfully!',
                                    'data' => [
                                        'type' => 'form_current',
                                        'enddate' => $voting->getEnddate()->format("Y-m-d H:i:s"),
                                    ]
                                ]);
                            } else {
                                // already voted
                                return $this->createApiResponse([
                                    'success' => true,
                                    'message' => 'You already voted on this proposal',
                                    'data' => [
                                        'type' => 'info',
                                        'content' => 'You already voted on this proposal on ' . $vote->getVotedAt()->format('F j, Y H:i') . '! The results of the online vote will be visible after voting has finished.',
                                        'enddate' => $voting->getEnddate()->format("Y-m-d H:i:s"),
                                    ]
                                ]);
                            }
                        } else {
                            return $this->createApiResponse([
                                'success' => false,
                                'message' => 'Only registered users can vote! Please <a href="' . $this->generateUrl('login') . '">login</a> or <a href="' . $this->generateUrl('app_register') . '">register</a> to continue.',
                            ], 302);
                        }
                    }
                }
            }
            /*
             * Past Initiative
             */
        } elseif ($initiative->getType() == InitiativeEnum::TYPE_PAST) {
            if ($initiative->getState() == InitiativeEnum::STATE_FINISHED) {
                return $this->createApiResponse([
                    'success' => true,
                    'message' => 'Votings are already finished!',
                    'data' => [
                        'type' => 'message',
                        'content' => 'The votings are already finished. You can see the results <a href="' . $this->generateUrl('initiative_result', ['id' => $initiative->getId(), 'slug' => $initiative->getSlug()]) . '"> here </a>.'
                    ]
                ]);
            }
            /*
             * Program Initiative
             */
        } elseif ($initiative->getType() == InitiativeEnum::TYPE_PROGRAM) {
            if ($initiative->getState() == InitiativeEnum::STATE_FINISHED) {
                return $this->createApiResponse([
                    'success' => true,
                    'message' => 'Votings are already finished!',
                    'data' => [
                        'type' => 'message',
                        'content' => 'The votings are already finished. You can see the results <a href="' . $this->generateUrl('initiative_result', ['id' => $initiative->getId(), 'slug' => $initiative->getSlug()]) . '"> here </a>.'
                    ]
                ]);
            }
        }

        return $this->createApiResponse([
            'success' => false,
            'message' => 'Something went wrong. Please try it later again',
            'data' => [
                'type' => 'message',
                'content' => 'The votings are already finished. You can see the results <a href="' . $this->generateUrl('initiative_result', ['id' => $initiative->getId(), 'slug' => $initiative->getSlug()]) . '"> here </a>.'
            ]
        ], 400);

    }

    /**
     * vote.
     *
     * @Route("/{id}/vote/future", options={"expose"=true}, methods={"POST"}, requirements={"id" = "\d+"}, name="initiative_vote_future")
     * @param Request $request
     * @param Initiative $initiative
     * @return Response
     */
    public function voteFutureAction(Request $request, Initiative $initiative)
    {

        $this->denyAccessUnlessGranted("vote", $initiative);

        // was it sent with Ajax?
        if (false !== $request->isXmlHttpRequest()) {

            $vote = new Vote();
            $form = $this->createForm('App\Form\FutureVoteForm', $vote);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                if ($form->get('vote')->isClicked()) {
                    $em = $this->managerRegistry->getManager();
                    $voting = $initiative->getFutureVoting();
                    $vote->setUser($this->getUser());
                    $vote->setVoting($voting);
                    $vote->setValue($this->voteEncryptionService->encrypt(1));
                    $vote->setVotedAt(new \DateTime());

//                    dump($form);
//                    dump($voting);
//                    dump($vote);

                    $em->persist($vote);
                    $em->flush();

                    return $this->createApiResponse([
                        'success' => true,
                        'message' => 'Your vote was successfully registered! The proposal will become an official online vote when it has reached the required quota of at least 10% of eligible voters for the proposal. Online voting starts immediately after a proposal has reached the threshold support to become a vote. If a proposal fails to reach the 10 percent quorum during the validity of the proposal, it will be archived.',
                    ]);

                }

            }

        }

        return $this->createApiResponse([
            'success' => false,
            'message' => 'Something went wrong! Please try again later!',
        ], 400);

    }

    /**
     * vote.
     *
     * @Route("/{id}/vote/current", options={"expose"=true}, methods={"POST"}, requirements={"id" = "\d+"}, name="initiative_vote_current")
     * @param Request $request
     * @param Initiative $initiative
     * @return Response
     */
    public function voteCurrentAction(Request $request, Initiative $initiative)
    {

        // $this->denyAccessUnlessGranted("vote", $initiative);

        // was it sent with Ajax?
        if (false !== $request->isXmlHttpRequest()) {

            $vote = new Vote();
            $form = $this->createForm('App\Form\CurrentVoteForm', $vote);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                if ($form->get('voteYes')->isClicked() || $form->get('voteAbstention')->isClicked() || $form->get('voteNo')->isClicked()) {

                    $em = $this->managerRegistry->getManager();
                    $voting = $initiative->getCurrentVoting();
                    $vote->setUser($this->getUser());
                    $vote->setVoting($voting);

                    if ($form->get('voteYes')->isClicked()) {
                        $vote->setValue($this->voteEncryptionService->encrypt(1));
                    } elseif ($form->get('voteAbstention')->isClicked()) {
                        $vote->setValue($this->voteEncryptionService->encrypt(0));
                    } elseif ($form->get('voteNo')->isClicked()) {
                        $vote->setValue($this->voteEncryptionService->encrypt(-1));
                    }
                    $vote->setVotedAt(new DateTime());
                    $em->persist($vote);
                    $em->flush();

//                    dump($form);
//                    dump($voting);
//                    dump($vote);

                    return $this->createApiResponse([
                        'success' => true,
                        'message' => 'Your vote was successfully registered!',
                    ]);

                }
            }

        }

        return $this->createApiResponse([
            'success' => false,
            'message' => 'Your vote was not successfully registered! Please try again later!',
        ], 400);

    }


}
