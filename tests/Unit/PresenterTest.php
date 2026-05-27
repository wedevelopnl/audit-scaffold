<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Unit;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WeDevelop\Audit\Enum\AuditSource;
use WeDevelop\Audit\Exception\UnknownAuditLogException;
use WeDevelop\Audit\Presenter;
use WeDevelop\Audit\Tests\Fixtures\Audit\ConcreteAuditEntity;
use WeDevelop\Audit\Tests\Fixtures\Audit\ConcreteAuditLog;

final class PresenterTest extends TestCase
{
    /**
     * @param array<string, mixed>|null $criteria
     * @param array<string, mixed> $expectedCriteria
     */
    #[DataProvider('pagingCases')]
    public function testFetchTranslatesPagingIntoLimitAndOffset(
        ?array $criteria,
        ?int $page,
        int $pageSize,
        array $expectedCriteria,
        ?int $expectedLimit,
        ?int $expectedOffset,
    ): void {
        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->once())
            ->method('findBy')
            ->with($expectedCriteria, ['createdAt' => 'DESC'], $expectedLimit, $expectedOffset)
            ->willReturn([]);

        $presenter = new Presenter($this->registryFor($repository), ConcreteAuditEntity::class);

        iterator_to_array($presenter->fetch($criteria, $page, $pageSize));
    }

    /** @return iterable<string, array{?array<string, mixed>, ?int, int, array<string, mixed>, ?int, ?int}> */
    public static function pagingCases(): iterable
    {
        yield 'no page means no limit or offset' => [null, null, 50, [], null, null];
        yield 'first page starts at offset zero' => [['source' => 'ui'], 1, 10, ['source' => 'ui'], 10, 0];
        yield 'third page offsets by two pages' => [null, 3, 10, [], 10, 20];
        yield 'page zero clamps the offset to zero' => [null, 0, 10, [], 10, 0];
    }

    public function testFetchReconstructsAuditLogsFromEntities(): void
    {
        $entity = ConcreteAuditEntity::create(1, ConcreteAuditLog::class, AuditSource::UI, new \DateTimeImmutable());
        $repository = $this->createMock(ObjectRepository::class);
        $repository->method('findBy')->willReturn([$entity]);

        $presenter = new Presenter($this->registryFor($repository), ConcreteAuditEntity::class);

        $logs = iterator_to_array($presenter->fetch());

        self::assertCount(1, $logs);
        self::assertInstanceOf(ConcreteAuditLog::class, $logs[0]);
    }

    public function testFetchThrowsWhenTheActionIsNotAnAuditLog(): void
    {
        $entity = ConcreteAuditEntity::create(1, \stdClass::class, AuditSource::UI, new \DateTimeImmutable());
        $repository = $this->createMock(ObjectRepository::class);
        $repository->method('findBy')->willReturn([$entity]);

        $presenter = new Presenter($this->registryFor($repository), ConcreteAuditEntity::class);

        $this->expectException(UnknownAuditLogException::class);
        iterator_to_array($presenter->fetch());
    }

    /** @param ObjectRepository<ConcreteAuditEntity> $repository */
    private function registryFor(ObjectRepository $repository): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getRepository')->willReturn($repository);

        return $registry;
    }
}
