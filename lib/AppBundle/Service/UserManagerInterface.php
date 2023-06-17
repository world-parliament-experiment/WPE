<?php

namespace AppBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use AppBundle\Entity\User;

interface UserManagerInterface
{
   
    public function createUser($username, $password, $email);
   
    public function updateUser(User $user);

    public function deleteUser(User $user);
   
    public function getUserById($id);

    public function getUserByUsername($username);
   
    public function getAllUsers();

    public function findUserByEmail($email);

    public function findUserByConfirmationToken($token);

   
}