<?php declare(strict_types=1);

namespace WeDevelop\Audit;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use WeDevelop\Audit\Entity\AuditEntityInterface;
use WeDevelop\Audit\Exception\UnknownAuditLogException;

readonly class Presenter
{
    public const DEFAULT_PAGE_SIZE = 50;

    /** @var ObjectRepository<AuditEntityInterface> */
    private ObjectRepository $repository;

    /** @param class-string<AuditEntityInterface> $entityClass */
    public function __construct(
        ManagerRegistry $registry,
        string $entityClass,
    ) {
        $this->repository = $registry->getRepository($entityClass);
    }

    /**
     * @param array<string, mixed> $criteria
     * @return iterable<AuditLogInterface>
     */
    public function fetch(
        ?array $criteria = null,
        ?int $page = null,
        int $pageSize = self::DEFAULT_PAGE_SIZE,
    ): iterable {
        $limit = null !== $page ? $pageSize : null;
        $offset = null !== $page ? max($page - 1, 0) * $pageSize : null;
        $entities = $this->repository->findBy($criteria ?? [], ['createdAt' => 'DESC'], $limit, $offset);
        /** @var AuditEntityInterface $entity */
        foreach ($entities as $entity) {
            /** @var class-string<AuditLogInterface> $auditLogClass */
            $auditLogClass = $entity->getAction();
            if (!is_a($auditLogClass, AuditLogInterface::class, true)) {
                throw new UnknownAuditLogException($entity);
            }
            yield $auditLogClass::fromEntity($entity);
        }
    }
}
