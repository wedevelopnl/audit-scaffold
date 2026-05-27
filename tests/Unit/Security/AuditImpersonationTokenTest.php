<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use WeDevelop\Audit\Security\AuditImpersonationToken;
use WeDevelop\Audit\Security\AuditToken;
use WeDevelop\Audit\Tests\Fixtures\Security\FakeUser;

final class AuditImpersonationTokenTest extends TestCase
{
    public function testRetainsImpersonatedUserOriginalTokenAndOriginalRoles(): void
    {
        $impersonated = new FakeUser('alice', ['ROLE_USER']);
        $admin = new FakeUser('admin', ['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH']);
        $originalToken = new AuditToken($admin);

        $token = new AuditImpersonationToken($impersonated, $originalToken);

        self::assertInstanceOf(SwitchUserToken::class, $token);
        self::assertSame(AuditToken::DEFAULT_FIREWALL_NAME, $token->getFirewallName());
        self::assertSame($impersonated, $token->getUser());
        self::assertSame($originalToken, $token->getOriginalToken());
        // Roles come from the original (impersonating) user, not the target.
        self::assertSame(['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH'], $token->getRoleNames());
    }

    public function testAcceptsCustomFirewallName(): void
    {
        $token = new AuditImpersonationToken(
            new FakeUser('alice'),
            new AuditToken(new FakeUser('admin')),
            'main',
        );

        self::assertSame('main', $token->getFirewallName());
    }
}
