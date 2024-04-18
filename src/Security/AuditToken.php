<?php declare(strict_types=1);

namespace WeDevelop\Audit\Security;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

class AuditToken extends UsernamePasswordToken
{
    public const DEFAULT_FIREWALL_NAME = 'audit';

    public function __construct(UserInterface $user, ?string $firewallName = null)
    {
        parent::__construct($user, $firewallName ?? self::DEFAULT_FIREWALL_NAME, $user->getRoles());
    }
}
