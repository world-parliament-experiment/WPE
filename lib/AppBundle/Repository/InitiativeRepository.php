<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Category;
use AppBundle\Entity\User;
use AppBundle\Entity\Voting;
use AppBundle\Enum\CommentEnum;
use AppBundle\Enum\InitiativeEnum;
use AppBundle\Enum\VotingEnum;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\Config\Definition\IntegerNode;

/**
 * InitiativeRepository
 *
 * This class was generated by the PhpStorm "Php Annotations" Plugin. Add your own custom
 * repository methods below.
 */
class InitiativeRepository extends EntityRepository
{

    public function futureStatus()
    {
        return $this->createQueryBuilder('initiative')
            ->andWhere('initiative.type = :type')
            ->andWhere('initiative.state = :state')
            ->setParameters([
                'state' => InitiativeEnum::STATE_ACTIVE,
                'type' => InitiativeEnum::TYPE_FUTURE,
            ])
            ->getQuery()
            ->execute();
    }

    public function pastStatus()
    {
        return $this->createQueryBuilder('initiative')
            ->andWhere('initiative.type = 1')
            ->getQuery()
            ->execute();


    }
    public function currentStatus()
    {
        return $this->createQueryBuilder('initiative')
            ->andWhere('initiative.type = 2')
            ->getQuery()
            ->execute();


    }
    public function programStatus()
    {
        return $this->createQueryBuilder('initiative')
            ->andWhere('initiative.type = 3')
            ->getQuery()
            ->execute();


    }
    public function program()
    {
        return $this->createQueryBuilder('initiative')
            ->andWhere('initiative.type = 3')
            ->setMaxResults(3)
            ->addOrderBy('initiative.createdAt', 'asc')
            ->getQuery()
            ->execute();


    }
    public function future()
    {
        return $this->createQueryBuilder('initiative')
            ->andWhere('initiative.type = 0')
            ->andWhere('initiative.state = 1')
            ->setMaxResults(25)
            ->addOrderBy('initiative.createdAt', 'desc')
            ->getQuery()
            ->execute();


    }
    public function current()
    {
        return $this->createQueryBuilder('initiative')
            ->andWhere('initiative.type = 1')
            ->andWhere('initiative.state = 1')
            ->setMaxResults(25)
            ->addOrderBy('initiative.createdAt', 'desc')
            ->getQuery()
            ->execute();


    }
    public function past()
    {
        return $this->createQueryBuilder('initiative')
            ->andWhere('initiative.type = 2')
            ->setMaxResults(3)
            ->addOrderBy('initiative.createdAt', 'asc')
            ->getQuery()
            ->execute();

    }

    public function random()
    {
        return $this->createQueryBuilder('initiative')
            ->andWhere('initiative.type = :type')
            ->setParameter('type', InitiativeEnum::TYPE_CURRENT)
            ->setMaxResults(3)
            ->orderBy('RANDOM()')
            ->getQuery()
            ->execute();
    }

    public function slider($maxResults)
    {
        return $this->createQueryBuilder('initiative')
            ->leftJoin('initiative.category', 'c')
            ->andWhere('initiative.type IN (0,1)')
            ->andWhere('initiative.state = 1')
            ->andWhere('c.country IN (:country)')
            ->setParameters([
                'country' => ['UN']
            ])
            ->setMaxResults($maxResults)
            ->orderBy('initiative.publishedAt', 'DESC')
            ->getQuery()
            ->execute();
    }

    public function getDraftInitiativesByUser(User $user)
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.category', 'c')
            ->andWhere('i.state = 0')
            ->andWhere('i.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('i.updatedAt')
            ->getQuery()
            ->execute();
    }
    public function getActiveInitiativesByUser(User $user)
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.category', 'c')
            ->andWhere('i.state = 1')
            ->andWhere('i.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('i.updatedAt')
            ->getQuery()
            ->execute();
    }

    /**
     * @param User $user
     * @return mixed
     */
    public function getFinishedInitiativesByUser(User $user)
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.category', 'c')
            ->andWhere('i.state = 2')
            ->andWhere('i.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('i.updatedAt')
            ->getQuery()
            ->execute();
    }


    /**
     * @param $pattern
     * @param $orderBy
     * @param $orderDir
     * @return mixed
     */
    public function search($pattern, $orderBy, $orderDir)
    {
        $query = $this->createQueryBuilder('i')
            ->select(['i'])
            ->orderBy('i.'.$orderBy, $orderDir)
        ;
        if($pattern != ''){
            $query
                ->andWhere("(LOWER(i.title) LIKE :query OR LOWER(i.description) LIKE :query)")
                ->setParameters([
                    'query' => '%'.strtolower($pattern).'%',
                ]);
        }
        return $query
            ->getQuery()
            ->execute();
    }

    /**
     * @param $pattern
     * @param $orderBy
     * @param $orderDir
     * @return mixed
     */
    public function searchPublic($pattern, $orderBy, $orderDir)
    {
        $query = $this->createQueryBuilder('i')
            ->select(['i'])
            ->andWhere('i.state IN (1, 2)')
            ->orderBy('i.'.$orderBy, $orderDir)
        ;
        if($pattern != ''){
            $query
                ->andWhere("(LOWER(i.title) LIKE :query OR LOWER(i.description) LIKE :query)")
                ->setParameters([
                    'query' => '%'.strtolower($pattern).'%',
                ]);
        }
        return $query
            ->getQuery()
            ->execute();
    }

    /**
     * @param $pattern
     * @param $orderBy
     * @param $orderDir
     * @param $category
     * @param $type
     * @return mixed
     */
    public function searchCategoryType($pattern, $orderBy, $orderDir, $category, $type)
    {

        $type = InitiativeEnum::checkTypeName($type);

        if ($type !== false) {

            $qb = $this->createQueryBuilder('i')
                ->select(['i'])
                ->join("i.createdBy", "c")
                ->where('i.category = :category')
                ->setParameter('category', $category)
                ->andWhere('i.state = :state')
                ->andWhere('i.type = :type')
                ->setParameter('type', $type)
                ->orderBy($orderBy, $orderDir);

            if ($type === InitiativeEnum::TYPE_PAST ||
                $type === InitiativeEnum::TYPE_PROGRAM
            ) {
                $qb->setParameter('state', InitiativeEnum::STATE_FINISHED);
            } else {
                $qb->setParameter('state', InitiativeEnum::STATE_ACTIVE);
            }

            if ($pattern != '') {
                $qb
                    ->andWhere("(LOWER(i.title) LIKE :query OR LOWER(i.description) LIKE :query  OR LOWER(c.username) LIKE :query)")
                    ->setParameter('query', '%' . strtolower($pattern) . '%');
            }
            return $qb
                ->getQuery()
                ->execute();
        }
    }

    /**
 * @param $category
 * @return mixed
 * @throws NoResultException
 * @throws NonUniqueResultException
 */
    public function countAllProgramInitiatives( $category)
    {
        return $this->createQueryBuilder("i")
            ->select('count(i.id)')
            ->where('i.category = :category')
            ->setParameter('category', $category)
            ->andWhere('i.state = 2')
            ->andWhere('i.type = 3')
            ->getQuery()
            ->getSingleScalarResult();
    }
    /**
     * @param $category
     * @return mixed
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countAllTypeInitiatives( $category, $type)
    {

        $type = InitiativeEnum::checkTypeName($type);

        if ($type !== false) {

            $qb = $this->createQueryBuilder("i")
                ->select('count(i.id)')
                ->where('i.category = :category')
                ->setParameter('category', $category)
                ->andWhere('i.state = :state')
                ->andWhere('i.type = :type')
                ->setParameter('type', $type);

            if ($type === InitiativeEnum::TYPE_PAST ||
                $type === InitiativeEnum::TYPE_PROGRAM
            ) {
                $qb->setParameter('state', InitiativeEnum::STATE_FINISHED);
            } else {
                $qb->setParameter('state', InitiativeEnum::STATE_ACTIVE);
            }

            $qb
                ->getQuery()
                ->getSingleScalarResult();

        }
    }
    /**
     * @return mixed
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countAllInitiatives()
    {
        return $this->createQueryBuilder("i")
            ->select('count(i.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param User $user
     * @return mixed
     */
    public function getFavouritesByUser(User $user, $state)
    {
        $queryBuilder = $this->createQueryBuilder('i');

        $expr = $this->_em->getExpressionBuilder();

        $sub = $this
            ->createQueryBuilder('sub')
            ->select('f')
            ->from('AppBundle:Favourite', 'f')
            ->where('f.user = :user')
            ->andWhere('f.initiative = i.id');

        $query = $queryBuilder
            ->select(['i'])
            ->where($expr->exists($sub->getDQL() ))
            ->andWhere("i.state = :state")
            ->setParameters([
                'user' => $user,
                'state' => $state,
            ]);
        return $query
            ->getQuery()
            ->execute();
    }

    /**
     * All future initiatives with state active and voting state waiting
     *
     * @return mixed
     */
    public function getFutureInitiativesToActivate()
    {
        return $this->createQueryBuilder('i')
        ->select(['i', 'v'])
        ->leftJoin('i.votings', 'v')
        ->andWhere('i.state = :istate')
        ->andWhere('i.type = :itype')
        ->andWhere('v.state = :vstate')
        ->andWhere('v.type = :vtype')
        ->andWhere('v.startdate <= CURRENT_TIMESTAMP()')
        ->setParameters([
            'istate' => InitiativeEnum::STATE_ACTIVE,
            'itype' => InitiativeEnum::TYPE_FUTURE,
            'vstate' => VotingEnum::STATE_WAITING,
            'vtype' => VotingEnum::TYPE_FUTURE,
        ])
        ->orderBy('i.publishedAt')
        ->getQuery()
        ->execute();
    }

    /**
     * All future initiatives with state active and voting state waiting
     *
     * @return mixed
     */
    public function getCurrentInitiativesToActivate()
    {
        return $this->createQueryBuilder('i')
            ->select(['i', 'v'])
            ->leftJoin('i.votings', 'v')
            ->andWhere('i.state = :istate')
            ->andWhere('i.type = :itype')
            ->andWhere('v.state = :vstate')
            ->andWhere('v.type = :vtype')
            ->andWhere('v.startdate <= CURRENT_TIMESTAMP()')
            ->setParameters([
                'istate' => InitiativeEnum::STATE_ACTIVE,
                'itype' => InitiativeEnum::TYPE_CURRENT,
                'vstate' => VotingEnum::STATE_WAITING,
                'vtype' => VotingEnum::TYPE_CURRENT,
            ])
            ->orderBy('i.publishedAt')
            ->getQuery()
            ->execute();
    }

    /**
     * All future initiatives with state active and voting state open
     * @return mixed
     */
    public function getFutureInitiativesToEvaluate()
    {
        return $this->createQueryBuilder('i')
            ->select(['i', 'v'])
            ->leftJoin('i.votings', 'v')
            //->andWhere('i.state = :istate')
            ->andWhere('i.type = :itype')
            //->andWhere('v.state = :vstate')
            ->andWhere('v.type = :vtype')
//           ->andWhere('v.enddate <= CURRENT_TIMESTAMP()')
            ->setParameters([
                //'istate' => InitiativeEnum::STATE_ACTIVE,
                'itype' => InitiativeEnum::TYPE_FUTURE,
                //'vstate' => VotingEnum::STATE_OPEN,
                'vtype' => VotingEnum::TYPE_FUTURE,
            ])
            ->orderBy('i.publishedAt')
            ->getQuery()
            ->execute();
    }

    /**
     * All future initiatives with state active and voting state open
     * @return mixed
     */
    public function getCurrentInitiativesToEvaluate()
    {
        return $this->createQueryBuilder('i')
            ->select(['i', 'v'])
            ->leftJoin('i.votings', 'v')
            ->andWhere('i.state = :istate')
            ->andWhere('i.type = :itype')
            ->andWhere('v.state = :vstate')
            ->andWhere('v.type = :vtype')
            #->andWhere('v.enddate <= CURRENT_TIMESTAMP()')
            ->setParameters([
                'istate' => InitiativeEnum::STATE_ACTIVE,
                'itype' => InitiativeEnum::TYPE_CURRENT,
                'vstate' => VotingEnum::STATE_OPEN,
                'vtype' => VotingEnum::TYPE_CURRENT,
            ])
            ->orderBy('i.publishedAt')
            ->getQuery()
            ->execute();
    }

    /**
     * returns collection of votes for a voting
     *
     * @param Voting $voting
     * @return mixed
     */
    public function getVotesByVoting(Voting $voting)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('v')
            ->from('AppBundle:Vote', 'v')
            ->andWhere('v.voting = :voting')
            ->setParameters(['voting' => $voting->getId()])
            ->addOrderBy('v.user')
            ->getQuery()
            ->execute()
        ;
    }

    public function getCurrentVoters()
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('u.id')
            ->from('AppBundle:User', 'u')
            ->andWhere('u.enabled = true')
            ->getQuery()
            ->execute()
        ;
    }

    public function getCurrentDelegations()
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('d')
            ->from('AppBundle:Delegation', 'd')
            ->addOrderBy('d.user')
            ->getQuery()
            ->execute()
        ;
    }

    public function getMostViewedInitiatives()
    {

        return $this->createQueryBuilder('i')
            ->select(['i', 'c'])
            ->leftJoin('i.category', 'c')
            ->setMaxResults(3)
            ->addOrderBy('i.views', 'desc')
            ->andWhere('i.state IN (:state)')
            ->andWhere('c.country IN (:country)')
            ->setParameters([
                'state' => [InitiativeEnum::STATE_ACTIVE, InitiativeEnum::STATE_FINISHED],
                'country' => ['UN']
            ])
            ->getQuery()
            ->execute();

    }

    public function getMostPopularInitiatives()
    {

        return $this->createQueryBuilder('i')
            ->select(['i', 'c'])
            ->leftJoin('i.category', 'c')
            ->setMaxResults(3)
            ->addOrderBy('i.views', 'desc')
            ->andWhere('i.state = :state')
            ->andWhere('i.type IN (:type)')
            ->andWhere('c.country IN (:country)')
            ->setParameters([
                'state' => InitiativeEnum::STATE_ACTIVE,
                'type' => [InitiativeEnum::TYPE_FUTURE, InitiativeEnum::TYPE_CURRENT],
                'country' => ['UN']
            ])
            ->getQuery()
            ->execute();

    }
    public function getMostCommentedInitiatives()
    {

        return $this->getEntityManager()
            ->createQueryBuilder()
          //  return $this ->createQueryBuilder('i')
            ->select('i.id, count(c.initiative) AS comments, i.title, i.slug,i.views')
            ->from('AppBundle:Comment', 'c')
            ->leftJoin('c.initiative', 'i', Join::ON. 'c.initiative = i.id')
            ->groupBy('c.initiative, i.title, i.id')
            ->orderBy('comments', 'DESC')
            ->andWhere('c.state = :state')
            ->setParameter('state' ,CommentEnum::STATE_OPEN)
            ->setMaxResults(10)
            ->getQuery()
            ->execute();

    }

}