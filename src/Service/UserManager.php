<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use App\Entity\User;

class UserManager
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function createUser()
    {
        $user = new User();
        // $user->setUsername($username);
        // $user->setPassword($password);
        // $user->setEmail($email);

        // $this->entityManager->persist($user);
        // $this->entityManager->flush();

        return $user;
    }

    public function updateUser(User $user)
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function deleteUser(User $user)
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function getUserById($id)
    {
        return $this->entityManager->getRepository(User::class)->find($id);
    }

    public function getUserByUsername($username)
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
    }

    public function getAllUsers()
    {
        return $this->entityManager->getRepository(User::class)->findAll();
    }
    public function findUserByEmail($email)
    {
        return $this->entityManager->getRepository(User::class)->findOneByEmail($email);
    }

    public function findUserByConfirmationToken($token)
    {
        return $this->entityManager->getRepository(User::class)->findOneByConfirmationToken($token);
    }

    /**
     * Finds all users that are not enabled, have never logged in,
     * are not AI agents, have no activity or foreign key references,
     * and satisfy the OTP/registration age requirements:
     * - If OTP was requested (expireAt is set): expireAt must be at least 12 months ago.
     * - If OTP was never requested (expireAt is null): registeredAt must be older than 1 week.
     *
     * @param \DateTimeInterface|null $now Optional reference time (defaults to current time)
     * @return User[]
     */
    public function findInactiveUsersWithoutActivity(?\DateTimeInterface $now = null): array
    {
        $refTime = $now ?? new \DateTime();
        $twelveMonthsAgo = (clone $refTime)->modify('-12 months');
        $oneWeekAgo = (clone $refTime)->modify('-1 week');

        $dql = 'SELECT u FROM App\Entity\User u
            WHERE u.enabled = false
              AND u.lastLogin IS NULL
              AND (u.isAi = false OR u.isAi IS NULL)
              AND (
                  (u.expireAt IS NOT NULL AND u.expireAt <= :twelveMonthsAgo)
                  OR
                  (u.expireAt IS NULL AND (u.registeredAt IS NULL OR u.registeredAt <= :oneWeekAgo))
              )
              AND NOT EXISTS (SELECT 1 FROM App\Entity\Comment c WHERE c.createdBy = u OR c.updatedBy = u)
              AND NOT EXISTS (SELECT 1 FROM App\Entity\Initiative i WHERE i.createdBy = u OR i.updatedBy = u)
              AND NOT EXISTS (SELECT 1 FROM App\Entity\Vote v WHERE v.user = u)
              AND NOT EXISTS (SELECT 1 FROM App\Entity\Voter vt WHERE vt.user = u)
              AND NOT EXISTS (SELECT 1 FROM App\Entity\Delegation d WHERE d.user = u OR d.truster = u)
              AND NOT EXISTS (SELECT 1 FROM App\Entity\Favourite f WHERE f.user = u OR f.friend = u)
              AND NOT EXISTS (SELECT 1 FROM App\Entity\UserImage ui WHERE ui.user = u)';

        return $this->entityManager->createQuery($dql)
            ->setParameter('twelveMonthsAgo', $twelveMonthsAgo)
            ->setParameter('oneWeekAgo', $oneWeekAgo)
            ->getResult();
    }

    /**
     * Deletes all users that are not enabled, have never logged in,
     * are not AI agents, and have no activity or foreign key references.
     *
     * @param \DateTimeInterface|null $now Optional reference time (defaults to current time)
     * @return int The number of deleted users
     */
    public function deleteInactiveUsers(?\DateTimeInterface $now = null): int
    {
        $users = $this->findInactiveUsersWithoutActivity($now);
        $deletedCount = 0;

        foreach ($users as $user) {
            /** @var User $user */
            $user->getSubscribedCategories()->clear();
            $this->entityManager->remove($user);
            $deletedCount++;
        }

        if ($deletedCount > 0) {
            try {
                $this->entityManager->flush();
            } catch (ForeignKeyConstraintViolationException $e) {
                return 0;
            }
        }

        return $deletedCount;
    }

}