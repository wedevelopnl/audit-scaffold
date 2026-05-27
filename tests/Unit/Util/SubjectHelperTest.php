<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Unit\Util;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WeDevelop\Audit\Exception\IdentifierStringConversionException;
use WeDevelop\Audit\Exception\SubjectNotConcreteException;
use WeDevelop\Audit\Tests\Fixtures\Doctrine\CompositeIdEntity;
use WeDevelop\Audit\Tests\Fixtures\Doctrine\IntegrationEntity;
use WeDevelop\Audit\Tests\Fixtures\Proxy\ChildProxy;
use WeDevelop\Audit\Tests\Fixtures\Proxy\OrphanProxy;
use WeDevelop\Audit\Tests\Fixtures\Proxy\ProxyParent;
use WeDevelop\Audit\Tests\Fixtures\Subject\JsonSerializableObject;
use WeDevelop\Audit\Tests\Fixtures\Subject\MethodIdObject;
use WeDevelop\Audit\Tests\Fixtures\Subject\PlainObject;
use WeDevelop\Audit\Tests\Fixtures\Subject\PropertyIdObject;
use WeDevelop\Audit\Tests\Fixtures\Subject\RequiredParamIdObject;
use WeDevelop\Audit\Tests\Fixtures\Subject\StringableObject;
use WeDevelop\Audit\Tests\Fixtures\Subject\UninitializedIdEntity;
use WeDevelop\Audit\Tests\Fixtures\Subject\UninitializedPropertyIdObject;
use WeDevelop\Audit\Tests\Support\ResetsSubjectHelperRegistry;
use WeDevelop\Audit\Util\SubjectHelper;

final class SubjectHelperTest extends TestCase
{
    use ResetsSubjectHelperRegistry;

    protected function tearDown(): void
    {
        $this->resetSubjectHelperRegistry();
    }

    public function testAssertObjectConcreteAcceptsConcreteClasses(): void
    {
        $this->expectNotToPerformAssertions();
        SubjectHelper::assertObjectConcrete(PropertyIdObject::class);
    }

    public function testAssertObjectConcreteRejectsAnonymousClasses(): void
    {
        $this->expectException(SubjectNotConcreteException::class);
        SubjectHelper::assertObjectConcrete(new class {});
    }

    public function testGetSubjectClassReturnsConcreteClass(): void
    {
        self::assertSame(PropertyIdObject::class, SubjectHelper::getSubjectClass(new PropertyIdObject()));
    }

    public function testGetSubjectClassUnwindsProxyToItsParent(): void
    {
        self::assertSame(ProxyParent::class, SubjectHelper::getSubjectClass(new ChildProxy()));
    }

    public function testGetSubjectClassRejectsParentlessProxy(): void
    {
        $this->expectException(SubjectNotConcreteException::class);
        SubjectHelper::getSubjectClass(new OrphanProxy());
    }

    public function testGetSubjectClassSwallowsRegistryErrorsAndFallsBack(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willThrowException(new \RuntimeException('registry down'));
        SubjectHelper::setManagerRegistry($registry);

        self::assertSame(PropertyIdObject::class, SubjectHelper::getSubjectClass(new PropertyIdObject()));
    }

    #[DataProvider('objectIdentifierCases')]
    public function testGetObjectIdentifier(mixed $expected, object $subject): void
    {
        self::assertSame($expected, SubjectHelper::getObjectIdentifier($subject));
    }

    /** @return iterable<string, array{mixed, object}> */
    public static function objectIdentifierCases(): iterable
    {
        yield 'doctrine id attribute' => [5, new IntegrationEntity(5)];
        yield 'composite doctrine id attributes' => [['first' => 1, 'second' => 'abc'], new CompositeIdEntity(1, 'abc')];
        yield 'id property' => [42, new PropertyIdObject()];
        yield 'getId() method' => [99, new MethodIdObject()];
        yield 'getId() requiring arguments is ignored' => [null, new RequiredParamIdObject()];
        yield 'uninitialized id attribute' => [null, new UninitializedIdEntity()];
        yield 'uninitialized id property' => [null, new UninitializedPropertyIdObject()];
        yield 'no identifier at all' => [null, new PlainObject()];
    }

    #[DataProvider('identifierStringCases')]
    public function testIdentifierToString(?string $expected, mixed $identifier): void
    {
        self::assertSame($expected, SubjectHelper::identifierToString($identifier));
    }

    /** @return iterable<string, array{?string, mixed}> */
    public static function identifierStringCases(): iterable
    {
        yield 'null stays null' => [null, null];
        yield 'true becomes the word true' => ['true', true];
        yield 'false becomes the word false' => ['false', false];
        yield 'integer is cast' => ['42', 42];
        yield 'string passes through' => ['foo', 'foo'];
        yield 'float is cast' => ['3.5', 3.5];
        yield 'array is json encoded' => ['{"a":1}', ['a' => 1]];
        yield 'stringable is cast' => ['stringable-id', new StringableObject()];
        yield 'json serializable is encoded' => ['{"type":"fixture","id":7}', new JsonSerializableObject()];
    }

    #[DataProvider('unconvertibleIdentifiers')]
    public function testIdentifierToStringThrowsForUnconvertibleValues(mixed $identifier): void
    {
        $this->expectException(IdentifierStringConversionException::class);
        SubjectHelper::identifierToString($identifier);
    }

    /** @return iterable<string, array{mixed}> */
    public static function unconvertibleIdentifiers(): iterable
    {
        yield 'array that cannot be json encoded' => [['unencodable' => \INF]];
        yield 'object that is not stringable' => [new PlainObject()];
    }
}
