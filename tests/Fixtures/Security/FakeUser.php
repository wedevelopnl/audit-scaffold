<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Fixtures\Security;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Minimal Symfony user used to build audit tokens in tests.
 */
class FakeUser implements UserInterface
{
    /** @param list<string> $roles */
    public function __construct(
        private string $identifier = 'user@example.com',
        private array $roles = ['ROLE_USER'],
    ) {}

    /** @return list<string> */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }
}
