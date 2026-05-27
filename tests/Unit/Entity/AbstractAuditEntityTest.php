<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use WeDevelop\Audit\Entity\Subject;
use WeDevelop\Audit\Enum\AuditSource;
use WeDevelop\Audit\Tests\Fixtures\Audit\ConcreteAuditEntity;
use WeDevelop\Audit\Tests\Fixtures\Doctrine\IntegrationEntity;

final class AbstractAuditEntityTest extends TestCase
{
    public function testExposesStoredColumns(): void
    {
        $createdAt = new \DateTimeImmutable('2026-01-02 03:04:05');
        $entity = ConcreteAuditEntity::create(
            id: 1,
            action: 'App\\Audit\\Something',
            source: AuditSource::API,
            createdAt: $createdAt,
            data: ['key' => 'value'],
            ipAddress: '203.0.113.5',
        );

        self::assertSame('App\\Audit\\Something', $entity->getAction());
        self::assertSame(AuditSource::API, $entity->getSource());
        self::assertSame($createdAt, $entity->getCreatedAt());
        self::assertSame(['key' => 'value'], $entity->getData());
        self::assertSame('203.0.113.5', $entity->getIpAddress());
    }

    public function testGetSubjectReturnsTheEmbeddedSubjectWhenPresent(): void
    {
        $subject = new Subject(IntegrationEntity::class, 5);
        $entity = ConcreteAuditEntity::create(1, 'App\\Action', AuditSource::UI, new \DateTimeImmutable(), subject: $subject);

        self::assertSame($subject, $entity->getSubject());
    }

    public function testGetSubjectRebuildsFromClassAndIdentifierColumns(): void
    {
        $entity = ConcreteAuditEntity::create(
            1,
            'App\\Action',
            AuditSource::UI,
            new \DateTimeImmutable(),
            subjectClass: IntegrationEntity::class,
            subjectIdentifier: '5',
        );

        $subject = $entity->getSubject();

        self::assertNotNull($subject);
        self::assertSame(IntegrationEntity::class, $subject->class);
        self::assertSame('5', $subject->identifier);
    }

    public function testGetSubjectReturnsNullWhenNoSubjectStored(): void
    {
        $entity = ConcreteAuditEntity::create(1, 'App\\Action', AuditSource::UI, new \DateTimeImmutable());

        self::assertNull($entity->getSubject());
    }
}
