<?php declare(strict_types=1);

namespace WeDevelop\Audit;

use WeDevelop\Audit\Entity\AuditEntityInterface;

interface AuditLogInterface extends AuditDataInterface
{
    public static function fromEntity(AuditEntityInterface $entity): self;

    public function createEntity(): AuditEntityInterface;
}
