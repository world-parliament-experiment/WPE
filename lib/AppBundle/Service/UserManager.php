<?php

namespace AppBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use AppBundle\Entity\User;

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

}