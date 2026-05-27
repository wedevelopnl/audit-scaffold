<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Unit\Entity;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WeDevelop\Audit\Entity\Subject;
use WeDevelop\Audit\Exception\SubjectNotConcreteException;
use WeDevelop\Audit\Tests\Fixtures\Doctrine\IntegrationEntity;
use WeDevelop\Audit\Tests\Fixtures\Subject\PropertyIdObject;

final class SubjectTest extends TestCase
{
    public function testFromObjectReturnsNullForNull(): void
    {
        self::assertNull(Subject::fromObject(null));
    }

    /** @param class-string $expectedClass */
    #[DataProvider('subjectObjects')]
    public function testFromObjectCapturesClassAndIdentifier(object $object, string $expectedClass, string $expectedIdentifier): void
    {
        $subject = Subject::fromObject($object);

        self::assertNotNull($subject);
        self::assertSame($expectedClass, $subject->class);
        self::assertSame($expectedIdentifier, $subject->identifier);
    }

    /** @return iterable<string, array{object, class-string, string}> */
    public static function subjectObjects(): iterable
    {
        yield 'doctrine entity' => [new IntegrationEntity(5), IntegrationEntity::class, '5'];
        yield 'plain object with id property' => [new PropertyIdObject(7), PropertyIdObject::class, '7'];
    }

    public function testConstructorStringifiesComplexIdentifiers(): void
    {
        $subject = new Subject(IntegrationEntity::class, ['tenant' => 1, 'ref' => 'abc']);

        self::assertSame('{"tenant":1,"ref":"abc"}', $subject->identifier);
    }

    public function testFromObjectRejectsAnonymousClasses(): void
    {
        $this->expectException(SubjectNotConcreteException::class);
        Subject::fromObject(new class {});
    }

    public function testConstructorRejectsAnonymousClasses(): void
    {
        $anonymous = new class {};

        $this->expectException(SubjectNotConcreteException::class);
        new Subject($anonymous::class);
    }
}
