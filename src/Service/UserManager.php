<?php

namespace App\Service;

use App\Entity\User;

class UserManager
{
    public function validateUser(User $user): bool
    {
        if (empty($user->getEmail())) {
            throw new \InvalidArgumentException('Email is required');
        }
        if (empty($user->getPassword())) {
            throw new \InvalidArgumentException('Password is required');
        }
        if (empty($user->getUsername())) {
            throw new \InvalidArgumentException('Username is required');
        }
        return true;
    }
}