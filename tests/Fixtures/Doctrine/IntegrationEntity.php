<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Fixtures\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * A minimal mapped entity used by the integration tests (and reused by unit
 * tests that need a single `#[ORM\Id]` property). The id is assigned, not
 * generated, so tests control it directly.
 */
#[ORM\Entity]
#[ORM\Table(name: 'integration_entity')]
class IntegrationEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string')]
    private string $name;

    public function __construct(int $id, string $name = 'fixture')
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
