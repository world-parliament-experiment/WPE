<?php
/**
 * Created by PhpStorm.
 * User: Konstantin Borchert
 * Date: 10.01.2017
 * Time: 13:29
 */

namespace AppBundle\Repository;

use AppBundle\Entity\User;
use AppBundle\Entity\Comment;
use AppBundle\Entity\UserImage;
use AppBundle\Entity\Category;
use AppBundle\Entity\Voting;
use AppBundle\Entity\Initiative;
use AppBundle\Entity\vote;
use AppBundle\Entity\Delegation;
use AppBundle\Enum\DelegationEnum;
use AppBundle\Enum\InitiativeEnum;
use AppBundle\Enum\VotingEnum;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Validator\Constraints\Count;

class UserRepository extends EntityRepository
{

    /**
     * gets user avatar image
     *
     * @param User $user
     * @return UserImage|null
     * @throws NonUniqueResultException
     */
    public function getUserAvatarImage(User $user)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('i')
            ->from(UserImage::class, 'i')
            ->andWhere('i.imageType = :type')
            ->andWhere('i.user = :user')
            ->setParameters(['type' => UserImage::USER_IMAGE_TYPE_AVATAR, 'user' => $user->getId()])
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    /**
     * gets user portrait image
     *
     * @param User $user
     * @return UserImage|null
     * @throws NonUniqueResultException
     */
    public function getUserPortraitImage(User $user)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('i')
            ->from('UserImage', 'i')
            ->andWhere('i.imageType = :type')
            ->andWhere('i.user = :user')
            ->setParameter('type', UserImage::USER_IMAGE_TYPE_PORTRAIT)
            ->setParameter('user', $user->getId())
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    /**
     *
     * returns Vote for Voting for a user if exists
     * @param User $user
     * @param Voting $voting
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function getUserVoteByVoting(User $user, Voting $voting)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('v')
            ->from(Vote::class, 'v')
            ->andWhere('v.user = :user')
            ->andWhere('v.voting = :voting')
            ->setParameter('user', $user->getId())
            ->setParameter('voting', $voting->getId())
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    /**
     * @param $pattern
     * @param $orders
     * @return mixed
     */
    public function search($pattern, $orders)
    {
        $query = $this->createQueryBuilder('u')
            ->select(['u']);

        foreach ($orders as $order) {
            $query->addOrderBy("u." . $order[0], $order[1]);
        }

        if ($pattern != '') {
            $query
                ->andWhere("(LOWER(u.firstname) LIKE :query OR LOWER(u.lastname) LIKE :query )")
                ->setParameters([
                    'query' => '%' . strtolower($pattern) . '%',
                ]);
        }

        return $query
            ->getQuery()
            ->execute();
    }

    /**
     * @param $pattern
     * @param $orders
     * @param bool $enabledOnly
     * @return mixed
     */
    public function assemblySearch($pattern, $orders, $enabledOnly=true)
    {
        $query = $this->createQueryBuilder('u')
            ->select(['u']);

        if ($enabledOnly === true)
            $query->andWhere("u.enabled = true");

        foreach ($orders as $order) {
            $query->addOrderBy("u." . $order[0], $order[1]);
        }

        if ($pattern != '') {
            $query
                ->andWhere("(LOWER(u.username) LIKE :query OR LOWER(u.city) LIKE :query )")
                ->setParameters([
                    'query' => '%' . strtolower($pattern) . '%',
                ]);
        }

        return $query
            ->getQuery()
            ->execute();
    }

    /**
     * @param bool $enabledOnly
     * @return mixed
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countAllUsers($enabledOnly=false)
    {
        $q = $this->createQueryBuilder("u")
            ->select('count(u.id)')
        ;

        if ($enabledOnly === true)
            $q->andWhere("u.enabled = true");

        return $q
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param User $user
     * @return QueryBuilder
     */
    public function getFriendsByUserQuery(User $user)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        return $qb
            ->select(["u"])
            ->from(User::class, "u")
            ->where(
                $qb->expr()->exists(
                    $this->getEntityManager()->createQueryBuilder()
                        ->select("f")
                        ->from("AppBundle:Favourite", "f")
                        ->where("f.user = :user")
                        ->andWhere("f.friend = u")
                        ->getDQL()
                )
            )
            ->orderBy('u.username')
            ->setParameters([
                'user' => $user
            ]);
    }

    /**
     * @param User $user
     * @return mixed
     */
    public function getFriendsByUser(User $user)
    {
        return $this->getFriendsByUserQuery($user)
            ->getQuery()
            ->execute();
    }

    /**
     * @param User $user
     * @param User $truster |null
     * @return QueryBuilder
     */
    public function getDelegationChoiceQuery(User $user, User $truster = null)
    {

        $qb = $this->getEntityManager()->createQueryBuilder();

        return $qb
            ->select(["u"])
            ->from(User::class, "u")
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->exists(
                        $this->getEntityManager()->createQueryBuilder()
                            ->select("f")
                            ->from("AppBundle:Favourite", "f")
                            ->where("f.user = :user")
                            ->andWhere("f.friend = u")
                            ->getDQL()
                    ),
                    $qb->expr()->eq("u", ":truster")
                )
            )
            ->orderBy('u.username')
            ->setParameters([
                'user' => $user,
                'truster' => $truster
            ]);
    }

    public function getMostCitizensByCountry()
    {

        return $this->createQueryBuilder("u")
            ->select('u.country')
            ->addSelect('COUNT(u.id) AS citizens')
            ->orderBy('citizens', 'DESC')
            ->where("u.enabled = true")
            ->groupBy('u.country')
            ->getQuery()
            ->execute();


    }

    public function getUserMostDelegations($max=10)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->addSelect('count(d.user) AS delegations, u.username, u.id')
            ->from(Delegation::class, 'd')
            ->leftJoin('d.truster', 'u')
            ->groupBy('u.username, u.id')
            ->orderBy('delegations', 'DESC')
            ->addOrderBy('u.username')
            ->setMaxResults($max)
            ->getQuery()
            ->execute();
   }

    public function getMostDelegationsByUser($max=10)
    {

        $categories = [];
        $users = [];
//        $trusters = [];
        $score = [];

        // all categories

        $q = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('c.id')
            ->from(Category::class, "c")
            ->getQuery();

        foreach ($q->execute() as $category) {
            $categories[$category['id']] = null;
        }

        // get all delegations with global scope

        $q = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("u.id AS user, t.id AS truster, t.username AS truster_username")
            ->from(Delegation::class, "d")
            ->leftJoin('d.user', 'u')
            ->leftJoin('d.truster', 't')
            ->where("d.scope = :scope")
            ->setParameter("scope", DelegationEnum::SCOPE_PLATFORM)
            ->getQuery();

        foreach ($q->execute() as $delegation) {
            foreach ($categories as $key=>$category) {
                $users[$delegation['user']][$key] = [ $delegation['truster'],  $delegation['truster_username'] ];
//                $trusters[$delegation['truster']][$delegation['user']][$key] = 1;
            }
        }

        // get all delegations with category scope

        $q = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("u.id AS user, t.id AS truster, t.username AS truster_username, c.id AS category")
            ->from(Delegation::class, "d")
            ->leftJoin('d.user', 'u')
            ->leftJoin('d.truster', 't')
            ->leftJoin('d.category', 'c')
            ->where("d.scope = :scope")
            ->setParameter("scope", DelegationEnum::SCOPE_CATEGORY)
            ->getQuery();

        foreach ($q->execute() as $delegation) {
            $users[$delegation['user']][$delegation['category']] = [ $delegation['truster'],  $delegation['truster_username'] ];
//            $trusters[$delegation['truster']][$delegation['user']][$delegation['category']] = 1;
        }
//
//        dump($users);
//        dump($trusters);

        foreach ($users as $uKey=>$category) {
            foreach ($category as $cKey=>$truster) {
                if (!isset($score[$truster[0]])) {
                    $score[$truster[0]] = [
                        "id" => $truster[0],
                        "username" => $truster[1],
                        "score" => 0,
                    ];
                }
                $score[$truster[0]]['score'] += 1;
            }
        }
        uasort($score, function ($a, $b) { return $a['score'] > $b['score'] ? -1 : 1; });
        return array_slice($score, 0, $max);
    }

    public function getMostCommentsByUser()
    {

        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('u.id, count(c.createdBy) AS comments, u.username')
            ->from(Comment::class, 'c')
            ->leftJoin('c.createdBy', 'u', Join::ON. 'c.createdBy = u.id')
            ->groupBy('c.createdBy, u.username, u.id')
            ->orderBy('comments', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->execute();

    }
    public function getMostInitiativesByUser()
    {

        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('u.id')
            ->addSelect('count(c.createdBy) AS initiatives, u.username')
            ->from(Initiative::class, 'c')
            ->leftJoin('c.createdBy', 'u', Join::ON. 'c.createdBy = u.id')
            ->andWhere('c.state != :istate OR c.state != :vstate' )
           // ->andWhere('c.state != :vstate')
            ->setParameters([
                'istate' => InitiativeEnum::STATE_DRAFT,

                'vstate' => InitiativeEnum::STATE_DELETED,

            ])


            ->groupBy('c.createdBy, u.username, u.id')
            ->orderBy('initiatives', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->execute();

    }
    public function getLastLogins()
    {

       return $this->createQueryBuilder('u')
           ->select("u.id, u.username, u.lastLogin")
           ->where("u.lastLogin IS NOT NULL")
           ->setMaxResults(5)
           ->orderBy('u.lastLogin', 'DESC')
           ->getQuery()
           ->execute();
    }

    public function getLastRegistrations()
    {

        return $this->createQueryBuilder('u')
            ->select("u.id, u.username, u.registeredAt")
            ->where("u.enabled = true")
            ->setMaxResults(5)
            ->addOrderBy('u.registeredAt', 'desc')
            ->getQuery()
            ->execute();


    }

}