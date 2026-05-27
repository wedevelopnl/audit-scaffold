<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Unit\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use WeDevelop\Audit\Enum\AuditSource;
use WeDevelop\Audit\Security\AuditImpersonationToken;
use WeDevelop\Audit\Security\AuditToken;
use WeDevelop\Audit\Tests\Fixtures\Audit\ConcreteAuditEntity;
use WeDevelop\Audit\Tests\Fixtures\Security\FakeUser;
use WeDevelop\Audit\ValueObject\Context;

final class ContextTest extends TestCase
{
    /** @param \Closure(): Context $factory */
    #[DataProvider('tokenlessFactories')]
    public function testTokenlessFactoriesHaveNoTokenOrIp(AuditSource $expectedSource, \Closure $factory): void
    {
        // Invoke the factory in the test body (not the provider) so it is counted
        // as covered — data providers run outside coverage measurement.
        $context = $factory();

        self::assertSame($expectedSource, $context->source);
        self::assertNull($context->token);
        self::assertNull($context->ip);
    }

    /** @return iterable<string, array{AuditSource, \Closure(): Context}> */
    public static function tokenlessFactories(): iterable
    {
        yield 'console' => [AuditSource::CONSOLE, static fn (): Context => Context::console()];
        yield 'job' => [AuditSource::JOB, static fn (): Context => Context::job()];
    }

    /** @param \Closure(Request, AuditToken): Context $factory */
    #[DataProvider('requestFactories')]
    public function testRequestFactoriesCaptureSourceTokenAndClientIp(AuditSource $expectedSource, \Closure $factory): void
    {
        $request = Request::create('/', server: ['REMOTE_ADDR' => '192.0.2.10']);
        $token = new AuditToken(new FakeUser());

        $context = $factory($request, $token);

        self::assertSame($expectedSource, $context->source);
        self::assertSame($token, $context->token);
        self::assertNotNull($context->ip);
        self::assertSame('192.0.2.10', $context->ip->address);
    }

    /** @return iterable<string, array{AuditSource, \Closure(Request, AuditToken): Context}> */
    public static function requestFactories(): iterable
    {
        yield 'ui' => [AuditSource::UI, static fn (Request $r, AuditToken $t): Context => Context::ui($r, $t)];
        yield 'api' => [AuditSource::API, static fn (Request $r, AuditToken $t): Context => Context::api($r, $t)];
        yield 'webhook with token' => [AuditSource::WEBHOOK, static fn (Request $r, AuditToken $t): Context => Context::webhook($r, $t)];
    }

    public function testWebhookDefaultsToNoTokenButStillCapturesIp(): void
    {
        $context = Context::webhook(Request::create('/', server: ['REMOTE_ADDR' => '192.0.2.3']));

        self::assertSame(AuditSource::WEBHOOK, $context->source);
        self::assertNull($context->token);
        self::assertSame('192.0.2.3', (string) $context->ip);
    }

    public function testUnknownAlwaysThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        Context::unknown();
    }

    public function testFromEntityWithoutUserHasNoToken(): void
    {
        $entity = ConcreteAuditEntity::create(1, 'App\\Action', AuditSource::JOB, new \DateTimeImmutable(), ipAddress: '192.0.2.5');

        $context = Context::fromEntity($entity);

        self::assertSame(AuditSource::JOB, $context->source);
        self::assertNull($context->token);
        self::assertSame('192.0.2.5', (string) $context->ip);
    }

    public function testFromEntityWithUserBuildsAnAuditToken(): void
    {
        $user = new FakeUser('alice');
        $entity = ConcreteAuditEntity::create(1, 'App\\Action', AuditSource::UI, new \DateTimeImmutable(), user: $user);

        $context = Context::fromEntity($entity);

        self::assertInstanceOf(AuditToken::class, $context->token);
        self::assertNotInstanceOf(AuditImpersonationToken::class, $context->token);
        self::assertSame($user, $context->token->getUser());
    }

    public function testFromEntityWithImpersonationBuildsAnImpersonationToken(): void
    {
        $user = new FakeUser('alice');
        $admin = new FakeUser('admin', ['ROLE_ADMIN']);
        $entity = ConcreteAuditEntity::create(
            1,
            'App\\Action',
            AuditSource::UI,
            new \DateTimeImmutable(),
            user: $user,
            impersonatedBy: $admin,
        );

        $context = Context::fromEntity($entity);

        self::assertInstanceOf(AuditImpersonationToken::class, $context->token);
        self::assertSame($user, $context->token->getUser());
        self::assertSame($admin, $context->token->getOriginalToken()->getUser());
    }

    public function testTransNamespacesTheSource(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->once())
            ->method('trans')
            ->with('source.console', [], 'audit', null)
            ->willReturn('Console Terminal');

        self::assertSame('Console Terminal', Context::console()->trans($translator));
    }
}
