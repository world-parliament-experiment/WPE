<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Comment;
use AppBundle\Entity\Initiative;
use AppBundle\Entity\User;
use AppBundle\Entity\Vote;
use AppBundle\Entity\Voting;
use AppBundle\Enum\CommentEnum;
use AppBundle\Enum\InitiativeEnum;
use AppBundle\Enum\VotingEnum;
use APY\BreadcrumbTrailBundle\Annotation\Breadcrumb;
use Doctrine\ORM\NonUniqueResultException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * Vote controller.
 *
 * @Route("/initiative")
 */
class VoteController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->_serializeGroups = ["simple"];
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
        $em = $this->getDoctrine()->getManager();
        if ($type == 'initiative') {
            $initiative = $em->getRepository(Initiative::class)->find($id);
        } else {
            $parentComment = $em->getRepository(Comment::class)->find($id);
            $initiative = $parentComment->getInitiative();
        }

        $this->denyAccessUnlessGranted("view", $initiative);
        $comment = new Comment();

        $form = $this->createForm('AppBundle\Form\CommentForm', $comment);

        $comment->setState(CommentEnum::STATE_OPEN);
        $comment->setInitiative($initiative);
        $comment->setLiked('0');
        $comment->setDisliked('0');
        $comment->setReported('0');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $comment = $form->getData();
            if ($type == 'comment') {
                $comment->setParent($parentComment);
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($comment);

            $em->flush();

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
     * @Method("GET")
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
        $em = $this->getDoctrine()->getManager();
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
     * Finds and displays a initiative entity.
     *
     * @Breadcrumb("breadcrumb.{initiative.typeName}.label", route={"name"="category_index", "parameters"={"type"="{initiative.typeName}"}}, attributes={"translate": true})
     * @Breadcrumb("{initiative.category.name}", route={"name"="category_type", "parameters"={"id"="{initiative.category.id}","slug"="{initiative.category.slug}","type"="{initiative.typeName}"}})
     * @Breadcrumb("{initiative.title}")
     * @Route("/{id}/{slug}", requirements={"id" = "\d+"}, methods={"GET"}, options={"expose"=true}, name="initiative_show")
     * @param Initiative $initiative
     * @return Response
     */
    public function showAction(Initiative $initiative)
    {

        $this->denyAccessUnlessGranted("view", $initiative);

        $em = $this->getDoctrine()->getManager();

        $initiative->incrementViews();

        $em->persist($initiative);
        $em->flush();

        $comment = new Comment();

        $form = $this->createForm('AppBundle\Form\CommentForm', $comment,
            [
                'action' => $this->generateUrl('initiative_save_reply', array('type' => 'initiative', 'id' => $initiative->getId()))
            ]);

        $comment->setState(CommentEnum::STATE_OPEN);
        $comment->setInitiative($initiative);
        $comment->setLiked('0');
        $comment->setDisliked('0');
        $comment->setReported('0');
        $comment->setParent(NULL);

        if ($initiative->getType() === 0) {
            return $this->render('Vote/show.html.twig', array(
                'initiative' => $initiative,
                'form' => $form->createView(),
                'repo' => $em->getRepository('Gedmo\Loggable\Entity\LogEntry'),
                'type' => 'proposal',
            ));
        } elseif ($initiative->getType() === 1) {
            return $this->render('Vote/show.html.twig', array(
                'initiative' => $initiative,
                'form' => $form->createView(),
                'repo' => $em->getRepository('Gedmo\Loggable\Entity\LogEntry'),
                'type' => 'vote',
            ));
        } elseif ($initiative->getType() === 2) {
            return $this->render('Vote/show.html.twig', array(
                'initiative' => $initiative,
                'form' => $form->createView(),
                'repo' => $em->getRepository('Gedmo\Loggable\Entity\LogEntry'),
                'type' => 'unsuccessful initiative',
            ));
        } else {
            return $this->render('Vote/show.html.twig', array(
                'initiative' => $initiative,
                'form' => $form->createView(),
                'repo' => $em->getRepository('Gedmo\Loggable\Entity\LogEntry'),
                'type' => 'adopted vote',
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
    public function showVoteAction(Initiative $initiative)
    {

        $this->denyAccessUnlessGranted("view", $initiative);

        $em = $this->getDoctrine()->getManager();

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
                                    'message' => 'You already voted for this initiative',
                                    'data' => [
                                        'type' => 'info',
                                        'content' => 'You already voted for this initiative at ' . $vote->getVotedAt()->format('F j, Y H:i') . '!',
                                        'enddate' => $voting->getEnddate()->format("Y-m-d H:i:s"),
                                    ]
                                ]);
                            }
                        } else {
                            return $this->createApiResponse([
                                'success' => false,
                                'message' => 'Only registered user can vote for this initiative! Please <a href="' . $this->generateUrl('fos_user_security_login') . '">login</a> or <a href="' . $this->generateUrl('fos_user_registration_register') . '">register</a> to continue.',
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
                                    'message' => 'You already voted for this initiative',
                                    'data' => [
                                        'type' => 'info',
                                        'content' => 'You already voted for this initiative at ' . $vote->getVotedAt()->format('F j, Y H:i') . '!',
                                        'enddate' => $voting->getEnddate()->format("Y-m-d H:i:s"),
                                    ]
                                ]);
                            }
                        } else {
                            return $this->createApiResponse([
                                'success' => false,
                                'message' => 'Only registered user can vote for this initiative! Please <a href="' . $this->generateUrl('fos_user_security_login') . '">login</a> or <a href="' . $this->generateUrl('fos_user_registration_register') . '">register</a> to continue.',
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
            $form = $this->createForm('AppBundle\Form\FutureVoteForm', $vote);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                if ($form->get('vote')->isClicked()) {
                    $em = $this->getDoctrine()->getManager();
                    $voting = $initiative->getFutureVoting();
                    $vote->setUser($this->getUser());
                    $vote->setVoting($voting);
                    $vote->setValue(1);

//                    dump($form);
//                    dump($voting);
//                    dump($vote);

                    $em->persist($vote);
                    $em->flush();

                    return $this->createApiResponse([
                        'success' => true,
                        'message' => 'Your vote was successfully registered!',
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

        $this->denyAccessUnlessGranted("vote", $initiative);

        // was it sent with Ajax?
        if (false !== $request->isXmlHttpRequest()) {

            $vote = new Vote();
            $form = $this->createForm('AppBundle\Form\CurrentVoteForm', $vote);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                if ($form->get('voteYes')->isClicked() || $form->get('voteAbstention')->isClicked() || $form->get('voteNo')->isClicked()) {

                    $em = $this->getDoctrine()->getManager();
                    $voting = $initiative->getCurrentVoting();
                    $vote->setUser($this->getUser());
                    $vote->setVoting($voting);

                    if ($form->get('voteYes')->isClicked()) {
                        $vote->setValue(1);
                    } elseif ($form->get('voteAbstention')->isClicked()) {
                        $vote->setValue(0);
                    } elseif ($form->get('voteNo')->isClicked()) {
                        $vote->setValue(-1);
                    }

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
