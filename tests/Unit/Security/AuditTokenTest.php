<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use WeDevelop\Audit\Security\AuditToken;
use WeDevelop\Audit\Tests\Fixtures\Security\FakeUser;

final class AuditTokenTest extends TestCase
{
    public function testUsesDefaultFirewallAndUserRoles(): void
    {
        $user = new FakeUser('alice', ['ROLE_USER', 'ROLE_EDITOR']);

        $token = new AuditToken($user);

        self::assertSame(AuditToken::DEFAULT_FIREWALL_NAME, $token->getFirewallName());
        self::assertSame($user, $token->getUser());
        self::assertSame(['ROLE_USER', 'ROLE_EDITOR'], $token->getRoleNames());
    }

    public function testAcceptsCustomFirewallName(): void
    {
        $token = new AuditToken(new FakeUser(), 'main');

        self::assertSame('main', $token->getFirewallName());
    }
}
