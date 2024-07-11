<?php declare(strict_types=1);

namespace WeDevelop\Audit\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type as MongoType;
use WeDevelop\Audit\Exception\IdentifierStringConversionException;
use WeDevelop\Audit\Exception\SubjectNotConcreteException;
use WeDevelop\Audit\Util\SubjectHelper;

#[ODM\EmbeddedDocument]
readonly class Subject
{
    /** @var class-string */
    #[ODM\Field(type: MongoType::STRING)]
    public string $class;

    #[ODM\Field(type: MongoType::STRING, nullable: true)]
    public ?string $identifier;

    /**
     * @return ($subject is null ? null : self)
     * @throws SubjectNotConcreteException
     * @throws IdentifierStringConversionException
     */
    public static function fromObject(?object $subject): ?self
    {
        return null !== $subject
            ? new self(SubjectHelper::getSubjectClass($subject), SubjectHelper::getObjectIdentifier($subject))
            : null;
    }

    /**
     * @param class-string $class
     * @param mixed|array<string, mixed> $identifier
     * @throws SubjectNotConcreteException
     * @throws IdentifierStringConversionException
     */
    public function __construct(string $class, mixed $identifier = null)
    {
        SubjectHelper::assertObjectConcrete($class);
        $this->class = $class;
        $this->identifier = SubjectHelper::identifierToString($identifier);
    }
}
