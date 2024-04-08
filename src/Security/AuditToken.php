<?php declare(strict_types=1);

namespace WeDevelop\Audit\Security;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

class AuditToken extends UsernamePasswordToken
{
    public function __construct(UserInterface $user)
    {
        parent::__construct($user, 'audit', []);
    }
}
