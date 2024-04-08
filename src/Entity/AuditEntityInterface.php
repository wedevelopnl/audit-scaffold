<?php declare(strict_types=1);

namespace WeDevelop\Audit\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use WeDevelop\Audit\AuditLogInterface;
use WeDevelop\Audit\Enum\AuditSource;

interface AuditEntityInterface
{
    /**
     * Since the entity *MUST* be constructed user-side (because the entity is
     * defined user-side), this is a helper method that can be used in
     * AuditLogInterface::createEntity().
     */
    public static function fromAuditLog(AuditLogInterface $auditLog): self;

    public function getUser(): ?UserInterface;

    public function getImpersonatedBy(): ?UserInterface;

    /** @return class-string<AuditLogInterface> */
    public function getAction(): string;

    public function getSource(): AuditSource;

    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Additional data that is useful to store with the audit log entry in the
     * database. The array returned from this method must be simple (it must be
     * something that can be returned from the json_decode() function).
     *
     * Instances of \JsonSerializable are not acceptable, as they will no longer
     * be objects when fetched from the database.
     *
     * @return array<string, mixed>
     */
    public function getData(): ?array;

    public function getSubject(): ?Subject;

    public function getIpAddress(): ?string;
}
