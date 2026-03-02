<?php

namespace App\Tests;

use App\Entity\User;
use App\Service\UserManager;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    public function testValidateUser(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');
        $user->setUsername('test');
        $userManager = new UserManager();
        $this->assertTrue($userManager->validateUser($user));
    }
    public function testValidateUserWithEmptyEmail(): void
    {
        $user = new User();
        $user->setPassword('password');
        $user->setUsername('test');
        $userManager = new UserManager();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email is required');
        $userManager->validateUser($user);
    }
    public function testValidateUserWithEmptyPassword(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setUsername('test');
        $userManager = new UserManager();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password is required');
        $userManager->validateUser($user);
    }
    public function testValidateUserWithEmptyUsername(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');
        $userManager = new UserManager();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Username is required');
        $userManager->validateUser($user);
    }
}
