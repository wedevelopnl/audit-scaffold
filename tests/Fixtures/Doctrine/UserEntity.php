<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Fixtures\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * A mapped entity that is also a Symfony user, for exercising Context::refDb().
 */
#[ORM\Entity]
#[ORM\Table(name: 'user_entity')]
class UserEntity implements UserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string')]
    private string $username;

    public function __construct(int $id, string $username = 'alice')
    {
        $this->id = $id;
        $this->username = $username;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    // Required by Symfony 7's UserInterface; removed from the interface in Symfony 8.
    public function eraseCredentials(): void {}
}
