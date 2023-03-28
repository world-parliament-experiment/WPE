<?php


namespace AppBundle\Security;


use AppBundle\Entity\Initiative;
use AppBundle\Entity\User;
use AppBundle\Entity\Voting;
use AppBundle\Enum\InitiativeEnum;
use AppBundle\Enum\VotingEnum;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class VotingVoter extends Voter
{

    const VOTE = 'vote';

    /**
     * @var AccessDecisionManagerInterface
     */
    private $accessDecisionManager;

    /**
     * InitiativeVoter constructor.
     * @param AccessDecisionManagerInterface $accessDecisionManager
     */
    public function __construct(AccessDecisionManagerInterface $accessDecisionManager)
    {
        $this->accessDecisionManager = $accessDecisionManager;
    }


    protected function supports($attribute, $subject)
    {

        // if the attribute isn't one we support, return false
        if (!in_array($attribute, array(self::VOTE))) {
            return false;
        }

        // only vote on Initiative objects inside this voter
        if (!$subject instanceof Voting) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {

        $user = $token->getUser();

        // you know $subject is a Voting object, thanks to supports
        /** @var Voting $voting */
        $voting = $subject;


        if (!$user instanceof User) {

            // the user must be logged in; if not, deny access
            return false;
        }


        switch ($attribute) {
            case self::VOTE:
                return $this->canVote($voting, $user, $token);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canVote(Voting $voting, User $user, TokenInterface $token)
    {

        if ($this->accessDecisionManager->decide($token, array('ROLE_USER'))) {
            if ($voting->getState() == VotingEnum::STATE_OPEN) {
                return true;
            }
        }

        return false;
    }

}