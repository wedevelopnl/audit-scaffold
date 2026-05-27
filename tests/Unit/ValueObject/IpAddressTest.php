<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Unit\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use WeDevelop\Audit\Enum\AuditSource;
use WeDevelop\Audit\Tests\Fixtures\Audit\ConcreteAuditEntity;
use WeDevelop\Audit\ValueObject\IpAddress;

final class IpAddressTest extends TestCase
{
    #[DataProvider('validAddresses')]
    public function testFromRequestAcceptsValidAddresses(string $address): void
    {
        $request = Request::create('/', server: ['REMOTE_ADDR' => $address]);

        $ip = IpAddress::fromRequest($request);

        self::assertNotNull($ip);
        self::assertSame($address, $ip->address);
        self::assertSame($address, (string) $ip);
    }

    /** @return iterable<string, array{string}> */
    public static function validAddresses(): iterable
    {
        yield 'ipv4' => ['192.168.1.10'];
        yield 'ipv6' => ['::1'];
    }

    public function testFromRequestReturnsNullWhenNoClientIp(): void
    {
        self::assertNull(IpAddress::fromRequest(new Request()));
    }

    public function testFromRequestRejectsInvalidAddress(): void
    {
        $request = Request::create('/', server: ['REMOTE_ADDR' => 'not-an-ip-address']);

        $this->expectException(\InvalidArgumentException::class);
        IpAddress::fromRequest($request);
    }

    #[DataProvider('entityAddresses')]
    public function testFromEntityReadsTheStoredAddress(?string $expected, ?string $stored): void
    {
        $entity = ConcreteAuditEntity::create(1, 'App\\Action', AuditSource::UI, new \DateTimeImmutable(), ipAddress: $stored);

        $ip = IpAddress::fromEntity($entity);

        self::assertSame($expected, null === $ip ? null : (string) $ip);
    }

    /** @return iterable<string, array{?string, ?string}> */
    public static function entityAddresses(): iterable
    {
        yield 'address present' => ['10.0.0.1', '10.0.0.1'];
        yield 'address missing' => [null, null];
    }
}
