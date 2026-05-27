<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Fixtures\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * Mapped entity with a composite identifier (two `#[ORM\Id]` properties).
 */
#[ORM\Entity]
#[ORM\Table(name: 'composite_id_entity')]
class CompositeIdEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private int $first;

    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    private string $second;

    public function __construct(int $first, string $second)
    {
        $this->first = $first;
        $this->second = $second;
    }

    public function getFirst(): int
    {
        return $this->first;
    }

    public function getSecond(): string
    {
        return $this->second;
    }
}
