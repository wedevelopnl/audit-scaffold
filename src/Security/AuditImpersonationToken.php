<?php declare(strict_types=1);

namespace WeDevelop\Audit\Security;

use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AuditImpersonationToken extends SwitchUserToken
{
    public function __construct(UserInterface $user, TokenInterface $original)
    {
        parent::__construct($user, 'audit', [], $original, null);
    }
}
