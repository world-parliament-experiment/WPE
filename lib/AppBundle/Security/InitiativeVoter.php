<?php


namespace AppBundle\Security;

use AppBundle\Entity\Initiative;
use AppBundle\Entity\User;
use AppBundle\Enum\InitiativeEnum;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class InitiativeVoter extends Voter
{

    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';
    const PUBLISH = 'publish';
    const CLOSE = 'close';
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


    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {

        // if the attribute isn't one we support, return false
        if (!in_array($attribute, array(self::VIEW, self::EDIT, self::DELETE, self::PUBLISH, self::CLOSE, self::VOTE))) {
            return false;
        }

        // only vote on Initiative objects inside this voter
        if (!$subject instanceof Initiative) {
            return false;
        }

        return true;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {

        $user = $token->getUser();

        // you know $subject is a Initiative object, thanks to supports
        /** @var Initiative $initiative */
        $initiative = $subject;


        if (!$user instanceof User) {

            if ($user == 'anon.' && $attribute == self::VIEW) {
                return $this->canViewAnonym($initiative);
            }

            // the user must be logged in; if not, deny access
            return false;
        }

        // ROLE_SUPERADMIN can do anything! The power!
//        if ($this->accessDecisionManager->decide($token, array('ROLE_SUPERADMIN'))) {
//            return true;
//        }


        switch ($attribute) {
            case self::VIEW:
                return $this->canView($initiative, $user, $token);
            case self::EDIT:
                return $this->canEdit($initiative, $user, $token);
            case self::DELETE:
                return $this->canDelete($initiative, $user, $token);
            case self::PUBLISH:
                return $this->canPublish($initiative, $user, $token);
            case self::CLOSE:
                return $this->canClose($initiative, $user, $token);
            case self::VOTE:
                return $this->canVote($initiative, $user, $token);
        }

        throw new \LogicException('This code should not be reached!');
    }

    /**
     * @param Initiative $initiative
     * @return bool
     */
    private function canViewAnonym(Initiative $initiative)
    {

        // only active and finished initiatives are visible for everyone
        if ($initiative->getState() == InitiativeEnum::STATE_ACTIVE ||
            $initiative->getState() == InitiativeEnum::STATE_FINISHED
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param Initiative $initiative
     * @param User $user
     * @param TokenInterface $token
     * @return bool
     */
    private function canView(Initiative $initiative, User $user, TokenInterface $token)
    {

        if ($this->accessDecisionManager->decide($token, array('ROLE_SUPERADMIN'))) {
            return true;
        }

        // only user can view is own initiatives in state draft
        if ($initiative->getState() == InitiativeEnum::STATE_DRAFT) {

            if ($initiative->getCreatedBy()->getId() === $user->getId()) {
                return true;
            }

        }

        // only active and finished initiatives are visible for everyone
        if ($initiative->getState() == InitiativeEnum::STATE_ACTIVE ||
            $initiative->getState() == InitiativeEnum::STATE_FINISHED
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param Initiative $initiative
     * @param User $user
     * @param TokenInterface $token
     * @return bool
     */
    private function canEdit(Initiative $initiative, User $user, TokenInterface $token)
    {

        if ($this->accessDecisionManager->decide($token, array('ROLE_SUPERADMIN'))) {
            return true;
        }

        if ($initiative->getState() == InitiativeEnum::STATE_DRAFT) {

            if ($initiative->getCreatedBy()->getId() === $user->getId()) {
                return true;
            }

        }

        return false;
    }

    /**
     * @param Initiative $initiative
     * @param User $user
     * @param TokenInterface $token
     * @return bool
     */
    private function canDelete(Initiative $initiative, User $user, TokenInterface $token)
    {

//        if ($this->accessDecisionManager->decide($token, array('ROLE_SUPERADMIN'))) {
//            return true;
//        }
//
        // User can only delete his initiative, when it still in draft state
        if ($initiative->getState() == InitiativeEnum::STATE_DRAFT) {

            if ($initiative->getCreatedBy()->getId() === $user->getId()) {
                return true;
            }

        }

        return false;
    }

    /**
     * @param Initiative $initiative
     * @param User $user
     * @param TokenInterface $token
     * @return bool
     */
    private function canPublish(Initiative $initiative, User $user, TokenInterface $token)
    {

        // initiative can only be published, when in draft state
        if ($initiative->getState() == InitiativeEnum::STATE_DRAFT) {

            if ($this->accessDecisionManager->decide($token, array('ROLE_SUPERADMIN'))) {
                return true;
            }

            if ($initiative->getCreatedBy()->getId() === $user->getId()) {
                return true;
            }

        }

        return false;
    }

    /**
     * @param Initiative $initiative
     * @param User $user
     * @param TokenInterface $token
     * @return bool
     */
    private function canClose(Initiative $initiative, User $user, TokenInterface $token)
    {

        // only super admins can close initiatives
        if ($this->accessDecisionManager->decide($token, array('ROLE_SUPERADMIN'))) {
            return true;
        }

        return false;
    }

    /**
     * @param Initiative $initiative
     * @param User $user
     * @param TokenInterface $token
     * @return bool
     */
    private function canVote(Initiative $initiative, User $user, TokenInterface $token)
    {

        if ($initiative->getState() == InitiativeEnum::STATE_ACTIVE) {

            if ($this->accessDecisionManager->decide($token, array('ROLE_USER')) && $user->isAccountNonLocked() && $user->isEnabled() ) {
                return true;
            }

        }

        return false;
    }

}