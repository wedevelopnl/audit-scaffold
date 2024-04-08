<?php declare(strict_types=1);

namespace WeDevelop\Audit\Entity;

use Doctrine\DBAL\Types\Types as DbType;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type as MongoType;
use Doctrine\ORM\Mapping as ORM;
use WeDevelop\Audit\AuditLogInterface;
use WeDevelop\Audit\Enum\AuditSource;

abstract class AbstractAuditEntity implements AuditEntityInterface
{
    /** @var class-string<AuditLogInterface> */
    #[ORM\Column(type: DbType::STRING)]
    #[ODM\Field(type: MongoType::STRING)]
    protected string $action;

    #[ORM\Column(type: DbType::STRING, enumType: AuditSource::class)]
    #[ODM\Field(type: MongoType::STRING, enumType: AuditSource::class)]
    protected AuditSource $source;

    #[ORM\Column(type: DbType::DATETIME_IMMUTABLE)]
    #[ODM\Field(type: MongoType::DATE_IMMUTABLE)]
    protected \DateTimeImmutable $createdAt;

    // Doctrine ORM does not support nullable embeddables.
    /** @var class-string|null */
    #[ORM\Column(type: DbType::STRING, nullable: true)]
    protected ?string $subjectClass = null;

    #[ORM\Column(type: DbType::STRING, nullable: true)]
    protected ?string $subjectIdentifier = null;

    // However, Mongo ODM does support nullable embeddables.
    #[ODM\EmbedOne(nullable: true, targetDocument: Subject::class)]
    protected ?Subject $subject = null;

    #[ORM\Column(type: DbType::STRING, length: 39, nullable: true)]
    #[ODM\Field(type: MongoType::STRING)]
    protected ?string $ipAddress = null;

    /** @var array<string, mixed> */
    #[ORM\Column(type: DbType::JSON, nullable: true)]
    #[ODM\Field(type: MongoType::HASH, nullable: true)]
    protected ?array $data = [];

    /** @return class-string<AuditLogInterface> */
    public function getAction(): string
    {
        return $this->action;
    }

    public function getSource(): AuditSource
    {
        return $this->source;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getSubject(): ?Subject
    {
        if (null !== $this->subject) {
            return $this->subject;
        }

        if (null !== $this->subjectClass) {
            return new Subject($this->subjectClass, $this->subjectIdentifier);
        }

        return null;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    /** @return array<string, mixed>|null */
    public function getData(): ?array
    {
        return $this->data;
    }
}
