<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Unit\Exception;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WeDevelop\Audit\Enum\AuditSource;
use WeDevelop\Audit\Exception\AuditExceptionInterface;
use WeDevelop\Audit\Exception\IdentifierStringConversionException;
use WeDevelop\Audit\Exception\SubjectNotConcreteException;
use WeDevelop\Audit\Exception\UnknownAuditLogException;
use WeDevelop\Audit\Tests\Fixtures\Audit\ConcreteAuditEntity;

final class ExceptionTest extends TestCase
{
    #[DataProvider('unconvertibleIdentifierTypes')]
    public function testIdentifierStringConversionExceptionReportsTheType(string $expectedType, mixed $identifier): void
    {
        $exception = new IdentifierStringConversionException($identifier);

        self::assertInstanceOf(AuditExceptionInterface::class, $exception);
        self::assertSame(
            sprintf('Cannot convert identifier of type "%s" to a string', $expectedType),
            $exception->getMessage(),
        );
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function unconvertibleIdentifierTypes(): iterable
    {
        yield 'array' => ['array', ['a' => 1]];
        yield 'int' => ['int', 123];
        yield 'object' => ['stdClass', new \stdClass()];
    }

    public function testIdentifierStringConversionExceptionChainsThePreviousError(): void
    {
        $previous = new \JsonException('boom');

        self::assertSame($previous, (new IdentifierStringConversionException(['a' => 1], $previous))->getPrevious());
    }

    public function testSubjectNotConcreteExceptionMessage(): void
    {
        $exception = new SubjectNotConcreteException();

        self::assertInstanceOf(AuditExceptionInterface::class, $exception);
        self::assertSame(
            'Subject not concrete (anonymous classes cannot be referenced in audit logs)',
            $exception->getMessage(),
        );
    }

    public function testUnknownAuditLogExceptionExposesTheOffendingEntityAndClass(): void
    {
        $entity = ConcreteAuditEntity::create(1, 'App\\Audit\\Gone', AuditSource::UI, new \DateTimeImmutable());

        $exception = new UnknownAuditLogException($entity);

        self::assertInstanceOf(AuditExceptionInterface::class, $exception);
        self::assertSame(
            'Audit Entity references an Audit Log class "App\Audit\Gone" that is unknown',
            $exception->getMessage(),
        );
        self::assertSame($entity, $exception->getEntity());
        self::assertSame('App\Audit\Gone', $exception->getAuditLogClass());
    }
}
