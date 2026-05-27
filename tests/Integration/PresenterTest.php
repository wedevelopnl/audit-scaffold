<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Integration;

use WeDevelop\Audit\Enum\AuditSource;
use WeDevelop\Audit\Exception\UnknownAuditLogException;
use WeDevelop\Audit\Presenter;
use WeDevelop\Audit\Tests\Fixtures\Audit\ConcreteAuditEntity;
use WeDevelop\Audit\Tests\Fixtures\Audit\ConcreteAuditLog;
use WeDevelop\Audit\Tests\Support\DoctrineTestCase;

final class PresenterTest extends DoctrineTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema([ConcreteAuditEntity::class]);
    }

    public function testFetchReturnsLogsNewestFirst(): void
    {
        $this->persistLog(1, '2026-01-01 10:00:00');
        $this->persistLog(2, '2026-01-03 10:00:00');
        $this->persistLog(3, '2026-01-02 10:00:00');
        $this->em->flush();

        $logs = iterator_to_array($this->presenter()->fetch());

        self::assertSame(
            ['2026-01-03 10:00:00', '2026-01-02 10:00:00', '2026-01-01 10:00:00'],
            array_map(static fn (object $log): string => $log->getLoggedAt()->format('Y-m-d H:i:s'), $logs),
        );
    }

    public function testFetchReturnsOnlyTheRequestedPage(): void
    {
        $this->persistLog(1, '2026-01-01 10:00:00');
        $this->persistLog(2, '2026-01-02 10:00:00');
        $this->persistLog(3, '2026-01-03 10:00:00');
        $this->em->flush();

        $page = iterator_to_array($this->presenter()->fetch(page: 2, pageSize: 2));

        self::assertCount(1, $page);
        self::assertSame('2026-01-01 10:00:00', $page[0]->getLoggedAt()->format('Y-m-d H:i:s'));
    }

    public function testFetchThrowsWhenAStoredActionIsNotAnAuditLog(): void
    {
        $this->em->persist(ConcreteAuditEntity::create(1, \stdClass::class, AuditSource::UI, new \DateTimeImmutable()));
        $this->em->flush();

        $this->expectException(UnknownAuditLogException::class);
        iterator_to_array($this->presenter()->fetch());
    }

    private function presenter(): Presenter
    {
        return new Presenter($this->registryReturningEntityManager(), ConcreteAuditEntity::class);
    }

    private function persistLog(int $id, string $createdAt): void
    {
        $this->em->persist(
            ConcreteAuditEntity::create($id, ConcreteAuditLog::class, AuditSource::UI, new \DateTimeImmutable($createdAt)),
        );
    }
}
