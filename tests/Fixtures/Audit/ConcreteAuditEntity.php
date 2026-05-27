<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Fixtures\Audit;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\User\UserInterface;
use WeDevelop\Audit\AuditLogInterface;
use WeDevelop\Audit\Entity\AbstractAuditEntity;
use WeDevelop\Audit\Entity\Subject;
use WeDevelop\Audit\Enum\AuditSource;

/**
 * Concrete audit entity for tests, mirroring the userland entity from the
 * tutorial: adds the identifier and the user/impersonatedBy associations that
 * AbstractAuditEntity leaves to the implementor.
 */
#[ORM\Entity]
#[ORM\Table(name: 'audit_log')]
class ConcreteAuditEntity extends AbstractAuditEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private int $id;
    private ?UserInterface $user = null;
    private ?UserInterface $impersonatedBy = null;

    /**
     * Test-only flexible factory. Userland code constructs entities through
     * fromAuditLog(); tests need to set arbitrary column values (e.g. an unknown
     * action class, or a specific createdAt for ordering assertions).
     *
     * @param class-string<AuditLogInterface>|string $action
     * @param array<string, mixed>|null $data
     */
    public static function create(
        int $id,
        string $action,
        AuditSource $source,
        \DateTimeImmutable $createdAt,
        ?Subject $subject = null,
        ?string $subjectClass = null,
        ?string $subjectIdentifier = null,
        ?array $data = null,
        ?string $ipAddress = null,
        ?UserInterface $user = null,
        ?UserInterface $impersonatedBy = null,
    ): self {
        $entity = new self();
        $entity->id = $id;
        $entity->action = $action;
        $entity->source = $source;
        $entity->createdAt = $createdAt;
        $entity->subject = $subject;
        $entity->subjectClass = $subjectClass;
        $entity->subjectIdentifier = $subjectIdentifier;
        $entity->data = $data;
        $entity->ipAddress = $ipAddress;
        $entity->user = $user;
        $entity->impersonatedBy = $impersonatedBy;

        return $entity;
    }

    public static function fromAuditLog(AuditLogInterface $auditLog): self
    {
        $context = $auditLog->getContext();
        $token = $context->token;
        $subject = $auditLog->getSubject();

        return self::create(
            id: 0,
            action: $auditLog::class,
            source: $context->source,
            createdAt: $auditLog->getLoggedAt(),
            subject: $subject,
            subjectClass: $subject?->class,
            subjectIdentifier: $subject?->identifier,
            data: $auditLog->getData(),
            ipAddress: $context->ip?->address,
            user: $token?->getUser(),
            impersonatedBy: $token instanceof SwitchUserToken
                ? $token->getOriginalToken()->getUser()
                : null,
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function getImpersonatedBy(): ?UserInterface
    {
        return $this->impersonatedBy;
    }
}
