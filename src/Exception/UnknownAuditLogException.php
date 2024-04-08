<?php declare(strict_types=1);

namespace WeDevelop\Audit\Exception;

use WeDevelop\Audit\Entity\AuditEntityInterface;

class UnknownAuditLogException extends \RuntimeException implements AuditExceptionInterface
{
    public function __construct(private readonly AuditEntityInterface $entity, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf(
            'Audit Entity references an Audit Log class "%s" that is unknown',
            $this->entity->getAction(),
        ), 0, $previous);
    }

    public function getEntity(): AuditEntityInterface
    {
        return $this->entity;
    }

    public function getAuditLogClass(): string
    {
        return $this->entity->getAction();
    }
}
