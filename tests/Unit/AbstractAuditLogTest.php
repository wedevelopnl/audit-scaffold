<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;
use WeDevelop\Audit\AbstractAuditLog;
use WeDevelop\Audit\Enum\AuditSource;
use WeDevelop\Audit\Security\AuditToken;
use WeDevelop\Audit\Tests\Fixtures\Audit\ConcreteAuditEntity;
use WeDevelop\Audit\Tests\Fixtures\Audit\ConcreteAuditLog;
use WeDevelop\Audit\Tests\Fixtures\Doctrine\IntegrationEntity;
use WeDevelop\Audit\Tests\Fixtures\Security\FakeUser;
use WeDevelop\Audit\ValueObject\Context;

final class AbstractAuditLogTest extends TestCase
{
    public function testFromEntityRejectsAbstractClasses(): void
    {
        $entity = ConcreteAuditEntity::create(1, 'irrelevant', AuditSource::CONSOLE, new \DateTimeImmutable());

        $this->expectException(\LogicException::class);
        AbstractAuditLog::fromEntity($entity);
    }

    public function testFromEntityReconstructsAConcreteLog(): void
    {
        $createdAt = new \DateTimeImmutable('2026-03-04 05:06:07');
        $user = new FakeUser('alice');
        $entity = ConcreteAuditEntity::create(
            id: 1,
            action: ConcreteAuditLog::class,
            source: AuditSource::UI,
            createdAt: $createdAt,
            subjectClass: IntegrationEntity::class,
            subjectIdentifier: '5',
            data: ['method' => 'totp'],
            ipAddress: '198.51.100.7',
            user: $user,
        );

        $log = ConcreteAuditLog::fromEntity($entity);

        self::assertInstanceOf(ConcreteAuditLog::class, $log);
        self::assertSame($createdAt, $log->getLoggedAt());
        self::assertSame(['method' => 'totp'], $log->getData());

        $subject = $log->getSubject();
        self::assertNotNull($subject);
        self::assertSame(IntegrationEntity::class, $subject->class);
        self::assertSame('5', $subject->identifier);

        $context = $log->getContext();
        self::assertSame(AuditSource::UI, $context->source);
        self::assertNotNull($context->ip);
        self::assertSame('198.51.100.7', $context->ip->address);
        self::assertInstanceOf(AuditToken::class, $context->token);
        self::assertSame($user, $context->token->getUser());
    }

    public function testTransNamespacesTheMessageAndForwardsParameters(): void
    {
        $log = ConcreteAuditLog::create(Context::console(), null, new \DateTimeImmutable(), null);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->once())
            ->method('trans')
            ->with('action.user.mfa.enabled', ['%user%' => 'alice'], 'audit', null)
            ->willReturn('Translated message');

        self::assertSame('Translated message', $log->trans($translator));
    }

    public function testGetInfoYieldsStringEntriesAndTranslatableMessages(): void
    {
        $log = ConcreteAuditLog::create(Context::console(), null, new \DateTimeImmutable(), null);

        $info = iterator_to_array($log->getInfo(), false);

        self::assertCount(2, $info);
        self::assertSame('user.mfa.enabled.summary', $info[0]);
        self::assertInstanceOf(TranslatableMessage::class, $info[1]);
        self::assertSame('info.user.mfa.enabled.method', $info[1]->getMessage());
        self::assertSame(['%name%' => 'totp'], $info[1]->getParameters());
        self::assertSame('audit', $info[1]->getDomain());
    }
}
