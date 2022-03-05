<?php

namespace AppBundle\Service;

use AppBundle\Entity\DelegatingVoter;
use AppBundle\Entity\Delegation;
use AppBundle\Entity\DirectVoter;
use AppBundle\Entity\Initiative;
use AppBundle\Entity\NonVoter;
use AppBundle\Entity\User;
use AppBundle\Entity\Vote;
use AppBundle\Entity\Voting;
use AppBundle\Enum\DelegationEnum;
use AppBundle\Enum\InitiativeEnum;
use AppBundle\Enum\VotingEnum;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\Validator\Constraints\Date;

class VotingManager
{

    const VOTE_ACCEPT = 1;
    const VOTE_ABSTENT = 0;
    const VOTE_REJECT = -1;

    const VOTE_MIN = 20;
    const VOTE_MAX = 45;

    const DELEGATION_SUCCESS = 0;
    const DELEGATION_ERROR_NOT_FOUND = 1;
    const DELEGATION_ERROR_CIRCLE = 2;
    const DELEGATION_ERROR_RECURSIVE = 3;
    const DELEGATION_ERROR_UNKNOWN = 4;

    private $currentUsers;
    private $currentDelegations;
    private $debug;

    /**
     * @var EntityManagerInterface
     */
    private $manager;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * VotingManager constructor.
     * @param EntityManagerInterface $manager
     * @param LoggerInterface $logger
     */

    public function __construct(EntityManagerInterface $manager, LoggerInterface $logger)
    {

        ini_set('xdebug.max_nesting_level', 1000);

        $this->manager = $manager;
        $this->logger = $logger;

    }

    /**
     * Set all active future initiatives with voting state waiting to voting state open
     * returns number of affected initiatives
     *
     * @param bool $debug
     * @return int
     */
    public function activateFutureVotings($debug=false) {

        $this->setDebugMode($debug);

        $this->logger->info('activate future votes...');

        $initiatives = $this->manager->getRepository('AppBundle:Initiative')->getFutureInitiativesToActivate();
        $cntVotings = 0;

        $this->manager->transactional(function(EntityManagerInterface $em) use ($initiatives, &$cntVotings) {
            foreach ($initiatives as $initiative) {
                /** @var Initiative $initiative */
                // $duration = $initiative->getDuration();

                $voting = $initiative->getFutureVoting();

                $enddate = new DateTime();
                $enddate->modify("+6 months");
                $voting->setEnddate($enddate);

                $voting->setState(VotingEnum::STATE_OPEN);
                $em->persist($voting);

                $cntVotings++;

            }
        });

        return $cntVotings;

    }

    /**
     * Set all active current initiatives with voting state waiting to voting state open
     * returns number of affected initiatives
     *
     * @param bool $debug
     * @return int
     */
    public function activateCurrentVotings($debug=false) {

        $this->setDebugMode($debug);

        $this->logger->info('activate current votings...');

        $initiatives = $this->manager->getRepository('AppBundle:Initiative')->getCurrentInitiativesToActivate();
        $cntVotings = 0;

        $this->manager->transactional(function(EntityManagerInterface $em) use ($initiatives, &$cntVotings) {
            foreach ($initiatives as $initiative) {

                /** @var Initiative $initiative */
                //$duration = $initiative->getDuration();

                $voting = $initiative->getCurrentVoting();

                $enddate = new DateTime();
                $enddate->modify("Sunday 19:00");
                $voting->setEnddate($enddate);

                $voting->setState(VotingEnum::STATE_OPEN);
                $em->persist($voting);

                $cntVotings++;
            }
        });

        return $cntVotings;

    }

    /**
     * evaluate future votings
     *
     * @param bool $randomize if true randomize votes
     * @return int
     */
    public function evaluateFutureVotings($randomize=false, $debug=false) {

        $this->loadCurrentUsers();
        $this->loadCurrentDelegations();
        $this->setDebugMode($debug);

        $this->logger->info('starting to evaluate future votes...');

        $initiatives = $this->manager->getRepository('AppBundle:Initiative')->getFutureInitiativesToEvaluate();
        $cntVotings = 0;

//        dump($this->currentDelegations);
//        dump($this->currentUsers);

        $this->manager->transactional(function(EntityManagerInterface $em) use ($initiatives, &$cntVotings, $randomize) {
            foreach ($initiatives as $initiative) {

                /** @var Initiative $initiative */
                $voting = $initiative->getFutureVoting();

                if ($randomize) $this->randomizeVoting($voting, true);

                $accepted = $this->evaluateVoting($voting, $initiative);

                if ($accepted == 1) {

                    $voting->setState(VotingEnum::STATE_FINISHED);
                    $em->persist($voting);

                    $initiative->setType(InitiativeEnum::TYPE_CURRENT);
                    $em->persist($initiative);

                    $startdate = new DateTime();
                    $startdate->modify("+2 minutes");

                    // if ($startdate < new DateTime()) {
                    //     $startdate->modify("tomorrow 20:00");
                    // }

                    $cVoting = new Voting();
                    $cVoting->settype(VotingEnum::TYPE_CURRENT);
                    $cVoting->setState(VotingEnum::STATE_WAITING);
                    $cVoting->setStartdate($startdate);
                    $cVoting->setInitiative($initiative);
                    $em->persist($cVoting);

                } elseif ($accepted == 2) {

                    $voting->setState(VotingEnum::STATE_FINISHED);
                    $em->persist($voting);

                    $initiative->setType(InitiativeEnum::TYPE_PAST);
                    $initiative->setState(InitiativeEnum::STATE_FINISHED);

                    $em->persist($initiative);

                } elseif ($accepted == 0) {
                    //do nothing
                }

                $cntVotings++;
            }
        });

        return $cntVotings;

    }

    /**
     * evaluate current votings
     *
     * @param bool $randomize if true randomize votes
     * @return int
     */
    public function evaluateCurrentVotings($randomize=false, $debug=false) {

        $this->loadCurrentUsers();
        $this->loadCurrentDelegations();
        $this->setDebugMode($debug);

        $this->logger->info('starting to evaluate current votes...');

        $initiatives = $this->manager->getRepository('AppBundle:Initiative')->getCurrentInitiativesToEvaluate();
        $cntVotings = 0;

//        dump($this->currentDelegations);
//        dump($this->currentUsers);

        $this->manager->transactional(function(EntityManagerInterface $em) use ($initiatives, &$cntVotings, $randomize) {

            foreach ($initiatives as $initiative) {

                /** @var Initiative $initiative */
                $voting = $initiative->getCurrentVoting();

                // $voting->setState(VotingEnum::STATE_FINISHED);
                // $em->persist($voting);

                if ($randomize) $this->randomizeVoting($voting, false);

                $outcome= $this->evaluateVoting($voting, $initiative);

                switch($outcome){
                    case 1:
                        $voting->setState(VotingEnum::STATE_FINISHED);
                        $initiative->setType(InitiativeEnum::TYPE_PROGRAM);
                        $initiative->setState(InitiativeEnum::STATE_FINISHED);
                        break;
                    case 2:
                        $voting->setState(VotingEnum::STATE_FINISHED);
                        $initiative->setType(InitiativeEnum::TYPE_PAST);
                        $initiative->setState(InitiativeEnum::STATE_FINISHED);
                        break;
                    case 0:
                        //do nothing, leave initiative open
                        break;
                }
                $em->persist($voting);
                $em->persist($initiative);

                $cntVotings++;
            }
        });

        return $cntVotings;

    }

    /**
     *  load all delegations
     */
    private function loadCurrentDelegations()
    {
        $this->currentDelegations = array();
        $delegations = $this->manager->getRepository('AppBundle:Initiative')->getCurrentDelegations();

        /** @var Delegation $delegation */
        foreach ($delegations as $delegation) {

            $user = $delegation->getUser()->getId();
            $truster = $delegation->getTruster()->getId();

            // skip if user delegates to himself
            if ($user === $truster) continue;

            if (!isset($this->currentDelegations[$user])) {
                $this->currentDelegations[$user] = [
                    'global' => false,
                    'category' => [],
                    'initiative' => [],
                ];
            }

            if ($delegation->getScope() === DelegationEnum::SCOPE_PLATFORM) {
                $this->currentDelegations[$user]['global'] = $truster;
            } elseif ($delegation->getScope() === DelegationEnum::SCOPE_CATEGORY) {
                $category = $delegation->getCategory()->getId();
                $this->currentDelegations[$user]['category'][$category] = $truster;
            } elseif ($delegation->getScope() === DelegationEnum::SCOPE_INITIATIVE) {
                $initiative = $delegation->getInitiative()->getId();
                $this->currentDelegations[$user]['initiative'][$initiative] = $truster;
            }

        }

        return true;
    }

    /**
     *  load all user who are eligible to vote
     */
    private function loadCurrentUsers()
    {

        $this->currentUsers = array();
        $users = $this->manager->getRepository('AppBundle:Initiative')->getCurrentVoters();

        /** @var User $user */
        foreach ($users as $user) {
            $this->currentUsers[$user['id']] = $user['id'];
        }

        return true;

    }

    /**
     * load all votes for a specific voting
     *
     * @param Voting $voting
     * @return array
     */
    private function loadVotes(Voting $voting)
    {

        $votesArr = array();
        $votes = $this->manager->getRepository('AppBundle:Initiative')->getVotesByVoting($voting);

        /** @var Vote $vote */
        foreach ($votes as $vote) {

            $user = $vote->getUser();

            $votesArr[$user->getId()] = [
                'user' => $user->getId(),
                'value' => $vote->getValue(),
            ];

        }

        return $votesArr;
    }

    /**
     * randomize votes for voting
     *
     * @param Voting $voting
     * @param bool $future  if true randomize future voting, if false randomize current voting
     * @return bool
     */
    private function randomizeVoting(Voting $voting, $future=false)
    {

        $this->logger->info('randomize votes for ' . ($future === true ? "future" : "current") . " voting [Initiative ID=" . $voting->getInitiative()->getId() . "] ...");

        mt_srand($this->makeSeed());

        $users = $this->currentUsers;

        if ($voting) {

            $this->manager->transactional(function(EntityManagerInterface $em) use ($users, $voting, $future) {


                $this->manager->createQueryBuilder()
                    ->delete('AppBundle:Vote', 'v')
                    ->where('v.voting = :voting')
                    ->setParameter('voting', $voting)
                    ->getQuery()->execute();

                foreach ($users as $user) {

                    // change to vote for an initiative between VOTE_MIN and VOTE_MAX percent

                    if (mt_rand(0, 100) < mt_rand(self::VOTE_MIN, self::VOTE_MAX)) {

                        /** @var User $u */
                        $u = $this->manager->getReference('AppBundle\Entity\User', $user);
                        $vote = new Vote;
                        $vote->setUser($u);
                        $vote->setVoting($voting);

                        if ($future === true) {
                            $vote->setValue(self::VOTE_ACCEPT);
                        } else {
                            $prob = [
                                self::VOTE_REJECT,
                                self::VOTE_REJECT,
                                self::VOTE_REJECT,
                                self::VOTE_REJECT,
                                self::VOTE_ABSTENT,
                                self::VOTE_ABSTENT,
                                self::VOTE_ACCEPT,
                                self::VOTE_ACCEPT,
                                self::VOTE_ACCEPT,
                                self::VOTE_ACCEPT
                            ];
                            $vote->setValue($prob[mt_rand(0,count($prob)-1)]);
                        }

                        $this->manager->persist($vote);
                        $this->manager->flush();
                    }

                }
            });

            return true;
        }

        return false;

    }

    /**
     * evaluate voting
     *
     * @param Voting $voting
     * @param Initiative $initiative
     * @return int
     */
    private function evaluateVoting(Voting $voting, Initiative $initiative)
    {

        if ($this->getDebugMode()) print("Evaluate voting (ID=" . $voting->getId() . ", " . $voting->getType() . ") for Initiative \"" . $initiative->getTitle() . "\" (ID=" . $initiative->getId() . ")\n" );
        if ($this->getDebugMode()) print("==================================================================================================\n");

        $nonVoter = array();
        $directVoter = array();
        $delegateVoter = array();

        // load votes
        $votes = $this->loadVotes($voting);

        // NON VOTERS
        // start with all eligible voters

        $nonVoter = $this->currentUsers;

        // DIRECT VOTERS
        // start to analyze all direct votes

        foreach ($votes as $vote) {

            // remove from non voters
            if (isset($nonVoter[$vote['user']])) unset($nonVoter[$vote['user']]);

            $directVoter[$vote['user']] = [
                'user' => $vote['user'],
                'value' =>$vote['value'],
                'weight' => 1,
            ];

        }

        // DELEGATE VOTERS

        $delegations = $this->currentDelegations;

        // remove all delegations which are not for the specific initiative or category
        foreach ($delegations as $key=>$delegation) {

            $initiative_id = $initiative->getId();
            $category_id = $initiative->getCategory()->getId();

            // delegation by initiative
            if (isset($delegation['initiative'][$initiative_id])) {

                $delegations[$key] = [
                    'global' => false,
                    'category' => false,
                    'initiative' => $delegation['initiative'][$initiative_id],
                ];

            // delegation by category
            } elseif (isset($delegation['category'][$category_id])) {
                $delegations[$key] = [
                    'global' => false,
                    'category' => $delegation['category'][$category_id],
                    'initiative' => false,
                ];

            // global delegation
            } elseif ($delegation['global'] !== false) {
                $delegations[$key] = [
                    'global' => $delegation['global'],
                    'category' => false,
                    'initiative' => false,
                ];

            } else {
                // !!! should reach this point
                // no delegation otherwise, remove from delegations listing
                unset($delegations[$key]);
            }

        }

        // find for all non voters a possible delegation
        foreach ($nonVoter as $key=>$user)
        {

            $path = array($user);

            $result = $this->findDelegation($user, $delegations, $directVoter, $path, 0);

            if ($result === self::DELEGATION_SUCCESS) {

                // delegation found
                $delegateVoter[$user] = [
                    'user' => $user,
                    'path' => $path,
                    'weight' => count($path) - 1,
                    'delegator' => $path[count($path) - 1],
                ];

                // set weight for directVoter

                if (isset($directVoter[$delegateVoter[$user]['delegator']])) {
                    $directVoter[$delegateVoter[$user]['delegator']]['weight'] = $directVoter[$delegateVoter[$user]['delegator']]['weight'] + 1;
                    $delegateVoter[$user]['value'] = $directVoter[$delegateVoter[$user]['delegator']]['value'];
                }

                if (isset($nonVoter[$user])) unset($nonVoter[$user]);

            } else {
                if (isset($nonVoter[$user])) {
                    $nonVoter[$user] = array("user" => $user);
                    if ($result === self::DELEGATION_ERROR_NOT_FOUND) {
                        $nonVoter[$user]['reason'] = 'no delegation found';
                        $nonVoter[$user]['path'] = $path;
                    } elseif ($result === self::DELEGATION_ERROR_CIRCLE) {
                        $nonVoter[$user]['reason'] = 'delegation circle found';
                        $nonVoter[$user]['path'] = $path;
                    } elseif ($result === self::DELEGATION_ERROR_UNKNOWN) {
                        $nonVoter[$user]['reason'] = 'unknown reason';
                        $nonVoter[$user]['path'] = $path;
                    }
                }
            }
        }

        $quorum = $voting->getQuorum();
        $consensus = $voting->getConsensus();
        $now = new Datetime("now");

        $results = [
            "eligibleVoters" => count($this->currentUsers),
            "votesTotal" => 0,
            "votesTotalDelegated" => 0,
            "votesAcception" => 0,
            "votesAcceptionDelegated" => 0,
            "votesRejection" => 0,
            "votesRejectionDelegated" => 0,
            "votesAbstention" => 0,
            "votesAbstentionDelegated" => 0,
            "accepted" => false,
            "rejected" => false,
            "nonVoterTotal" => 0,
            "quorum" => $quorum,
            "outcome" => 0,
        ];

        foreach ($directVoter as $dv) {
            if ($dv['value'] == self::VOTE_ACCEPT) {
                $results["votesAcception"] = $results["votesAcception"] + $dv['weight'];
                $results["votesAcceptionDelegated"] = $results["votesAcceptionDelegated"] + $dv['weight'] - 1;
            } elseif ($dv['value'] == self::VOTE_ABSTENT) {
                $results["votesAbstention"] = $results["votesAbstention"] + $dv['weight'];
                $results["votesAbstentionDelegated"] = $results["votesAbstentionDelegated"] + $dv['weight'] - 1;
            } elseif ($dv['value'] == self::VOTE_REJECT) {
                $results["votesRejection"] = $results["votesRejection"] + $dv['weight'];
                $results["votesRejectionDelegated"] = $results["votesRejectionDelegated"] + $dv['weight'] - 1;
            }
        }

        $results["votesTotal"] = $results["votesAcception"] + $results["votesAbstention"] + $results["votesRejection"];
        $results["votesTotalDelegated"] = $results["votesAcceptionDelegated"] + $results["votesAbstentionDelegated"] + $results["votesRejectionDelegated"];
        $results["nonVoterTotal"] = count($this->currentUsers) - $results["votesTotal"];
        
        switch($voting->getType()){
            case VotingEnum::TYPE_FUTURE: 
                if (($voting->getEnddate() > $now) &&
                    ($results["votesTotal"] > 0) &&
                    (($results["votesTotal"] / $results["eligibleVoters"]) > $quorum) &&
                    ($results["votesAcception"] > ($results["votesAbstention"]  + $results["votesRejection"]))
                ) {
                    $results['accepted'] = true;
                } elseif ($voting->getEnddate() < $now) {
                    $results['rejected'] = true;
                } 
                break;
            case VotingEnum::TYPE_CURRENT:
                if ($voting->getEnddate() > $now) {
                    if (($results["votesTotal"] > 0) &&
                    (($results["votesTotal"] / $results["eligibleVoters"]) > $quorum) &&
                    (($results["votesAcception"] / $results["eligibleVoters"]) > $consensus) &&
                    ($results["votesAcception"] > ($results["votesAbstention"]  + $results["votesRejection"]))
                    ) {
                        $results['accepted'] = true;
                    } elseif (($results["votesTotal"] > 0) &&
                    (($results["votesTotal"] / $results["eligibleVoters"]) > $quorum) &&
                    (($results["votesRejection"] / $results["eligibleVoters"]) > $consensus) &&
                    ($results["votesRejection"] > ($results["votesAbstention"]  + $results["votesAcception"]))
                    ) {
                        $results['rejected'] = true;
                    } 
                } else {
                    if (($results["votesTotal"] > 0) &&
                        (($results["votesTotal"] / $results["eligibleVoters"]) > $quorum) &&
                        ($results["votesAcception"] > ($results["votesAbstention"] + $results["votesRejection"]))
                    ) {
                        $results['accepted'] = true;
                    } else {
                        $results['rejected'] = true;
                    }
                } 
                break;
        } //endswitch

        if ( $results["rejected"] == true ) {
            $results["outcome"] = 2;
        } elseif ( $results["accepted"] == true ) {
            $results["outcome"] = 1;
        } else {
            $results["outcome"] = 0;
        }

        $this->manager->transactional(function(EntityManagerInterface $em) use ($voting, $results) {
            $voting->setEligibleVoters($results['eligibleVoters']);
            $voting->setVotesTotal($results["votesTotal"]);
            $voting->setVotesTotalDelegated($results["votesTotalDelegated"]);
            $voting->setVotesAcception($results["votesAcception"]);
            $voting->setVotesAcceptionDelegated($results["votesAcceptionDelegated"]);
            $voting->setVotesAbstention($results["votesAbstention"]);
            $voting->setVotesAbstentionDelegated($results["votesAbstentionDelegated"]);
            $voting->setVotesRejection($results["votesRejection"]);
            $voting->setVotesRejectionDelegated($results["votesRejectionDelegated"]);
            $voting->setAccepted($results["accepted"]);
            $voting->setRejected($results["rejected"]);
            $em->persist($voting);
        });

        if ( $results["outcome"] != 0 ) {
            $this->manager->transactional(function(EntityManagerInterface $em) use ($voting, $directVoter) {
                foreach ($directVoter as $vote) {
                    /** @var User $u */
                    $u = $this->manager->getReference('AppBundle\Entity\User', $vote['user']);

                    $dv = new DirectVoter();
                    $dv->setVoting($voting);
                    $dv->setUser($u);
                    $dv->setWeight($vote['weight']);
                    $dv->setValue($vote['value']);
                    $this->manager->persist($dv);
                }
            });

            $this->manager->transactional(function(EntityManagerInterface $em) use ($voting, $delegateVoter) {
                foreach ($delegateVoter as $vote) {
                    /** @var User $u */
                    $u = $em->getReference('AppBundle\Entity\User', $vote['user']);
                    $dv = new DelegatingVoter();
                    $dv->setVoting($voting);
                    $dv->setUser($u);
                    $dv->setWeight($vote['weight']);
                    $dv->setValue($vote['value']);
                    $dv->setDelegateUserIds($vote['path']);
                    $em->persist($dv);
                }
            });

            $this->manager->transactional(function(EntityManagerInterface $em) use ($voting, $nonVoter) {
                foreach ($nonVoter as $vote) {
                    /** @var User $u */
                    $u = $em->getReference('AppBundle\Entity\User', $vote['user']);
                    $dv = new NonVoter();
                    $dv->setVoting($voting);
                    $dv->setUser($u);
                    $dv->setDelegateUserIds($vote['path']);
                    $dv->setReason($vote['reason']);
                    $em->persist($dv);
                }
            });
        }

//        print("DELEGATIONS\n");
//        dump($delegations);
//        print("DIRECT VOTER\n");
//        dump($directVoter);
//        print("DELEGATE VOTER\n");
//        dump($delegateVoter);
//        print("NON VOTER\n");
//        dump($nonVoter);

        if ($this->getDebugMode()) dump($results);
        if ($this->getDebugMode()) print("==================================================================================================\n\n");

        return $results["outcome"];

    }

    /**
     * find delegation for an user
     *
     * @param $user
     * @param $delegations
     * @param $directVoter
     * @param $path
     * @param $depth
     * @return bool
     */
    private function findDelegation($user, $delegations, $directVoter, &$path, $depth)
    {

//        if (isset($delegations[$user]))
//            dump($delegations[$user]);
//            dump($user);
//            dump($path);
//            dump($depth);

        // no delegation
        if (!isset($delegations[$user])) {
            if ($this->getDebugMode()) print ("found no delegation => " . implode(', ', $path) . "\n");
            return self::DELEGATION_ERROR_NOT_FOUND;
        }

        // circle found
        if ($depth > 0 && in_array($user, array_slice($path, 0, -1), true)) {
            if ($this->getDebugMode()) print ("found circle => " . $user . " / " . $depth . " / " . implode(', ', $path) . "\n");
            return self::DELEGATION_ERROR_CIRCLE;
        }

//        if ($depth > 100) {
//            dump($path);
//            return self::DELEGATION_ERROR_RECURSIVE;
//        } // break recursion

        // look by initiative
        if ($delegations[$user]['initiative'] !== false) {
            $path[] = $delegations[$user]['initiative'];
            if (isset($directVoter[$delegations[$user]['initiative']])) {
                if ($this->getDebugMode()) print ("find direct voter (initiative) => " . implode(', ', $path) . "\n");
                return self::DELEGATION_SUCCESS;
            } else {
                return $this->findDelegation($delegations[$user]['initiative'], $delegations, $directVoter, $path, ++$depth);
            }
            // look by category
        } elseif ($delegations[$user]['category'] !== false) {
            $path[] = $delegations[$user]['category'];
            if (isset($directVoter[$delegations[$user]['category']])) {
                if ($this->getDebugMode()) print ("find direct voter (category) => " . implode(', ', $path) . "\n");
                return self::DELEGATION_SUCCESS;
            } else {
                return $this->findDelegation($delegations[$user]['category'], $delegations, $directVoter, $path, ++$depth);
            }
            // look by global delegation
        } elseif ($delegations[$user]['global'] !== false) {
            $path[] = $delegations[$user]['global'];
            if (isset($directVoter[$delegations[$user]['global']])) {
                if ($this->getDebugMode()) print ("find direct voter (global) => " . implode(', ', $path) . "\n");
                return self::DELEGATION_SUCCESS;
            } else {
                return $this->findDelegation($delegations[$user]['global'], $delegations, $directVoter, $path, ++$depth);
            }
        }

       // TODO:: Exception needed?
        if ($this->getDebugMode()) print("Unexpected end ... \n");
        return self::DELEGATION_ERROR_UNKNOWN;
    }

    /**
     * generate random seed
     *
     * @return float|int
     */
    private function makeSeed()
    {
        list($usec, $sec) = explode(' ', microtime());
        return $sec + $usec * 1000000;
    }

    /**
     * @param mixed $debug
     */
    public function setDebugMode($debug)
    {
        $this->debug = $debug;
    }

    /**
     * @return mixed
     */
    public function getDebugMode()
    {
        return $this->debug;
    }

}