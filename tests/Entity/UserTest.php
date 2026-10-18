<?php

namespace Tests\App\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testGetUsernameForNonAiUser()
    {
        $user = new User();
        $user->setUsername('john_doe');
        $user->setFirstname('John');
        $user->setLastname('Doe');
        $user->setIsAi(false);

        $this->assertEquals('john_doe', $user->getUsername());
    }

    public function testGetUsernameForAiUser()
    {
        $user = new User();
        $user->setUsername('ai_bot');
        $user->setFirstname('Julian Vane');
        $user->setLastname('United Kingdom');
        $user->setIsAi(true);

        $this->assertEquals('Julian Vane United Kingdom', $user->getUsername());
    }
}
