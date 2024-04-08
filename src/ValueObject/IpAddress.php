<?php declare(strict_types=1);

namespace WeDevelop\Audit\ValueObject;

use Symfony\Component\HttpFoundation\Request;
use WeDevelop\Audit\Entity\AuditEntityInterface;

final readonly class IpAddress implements \Stringable
{
    private function __construct(public string $address)
    {
        if (!filter_var($address, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4)
            && !filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
        ) {
            throw new \InvalidArgumentException(sprintf(
                'Value passed to value object %s is neither an IPv4 nor an IPv6 address',
                self::class,
            ));
        }
    }

    public static function fromRequest(Request $request): ?self
    {
        $address = $request->getClientIp();
        return null !== $address
            ? new self($address)
            : null;
    }

    public static function fromEntity(AuditEntityInterface $entity): ?self
    {
        $address = $entity->getIpAddress();
        return null !== $address
            ? new self($address)
            : null;
    }

    public function __toString(): string
    {
        return $this->address;
    }
}
