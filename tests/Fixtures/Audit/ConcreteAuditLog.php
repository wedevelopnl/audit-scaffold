<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Fixtures\Audit;

use WeDevelop\Audit\AbstractAuditLog;
use WeDevelop\Audit\Entity\AuditEntityInterface;
use WeDevelop\Audit\Entity\Subject;
use WeDevelop\Audit\ValueObject\Context;

/**
 * Concrete audit log for tests, exercising AbstractAuditLog's shared behaviour.
 */
final readonly class ConcreteAuditLog extends AbstractAuditLog
{
    /** @param array<string, mixed>|null $data */
    public static function create(
        Context $context,
        ?Subject $subject,
        \DateTimeImmutable $loggedAt,
        ?array $data,
    ): self {
        return new self($context, $subject, $loggedAt, $data);
    }

    public function createEntity(): AuditEntityInterface
    {
        return ConcreteAuditEntity::fromAuditLog($this);
    }

    public function getMessage(): string
    {
        return 'user.mfa.enabled';
    }

    /** @return array<string, string> */
    public function getParameters(): array
    {
        return ['user' => 'alice'];
    }

    /**
     * Two string-keyed entries: a string value (yielded as-is by getInfo()) and
     * an array value (rendered as a TranslatableMessage by getInfo()).
     *
     * @return iterable<string, string|array<string, string>>
     */
    protected function defineAdditionalInfo(): iterable
    {
        yield 'summary' => 'user.mfa.enabled.summary';
        yield 'method' => ['name' => 'totp'];
    }
}
