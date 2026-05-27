<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Fixtures\Subject;

use Doctrine\ORM\Mapping as ORM;

/**
 * Carries an `#[ORM\Id]` attribute on an uninitialized typed property, so
 * SubjectHelper::getIdAttributes() must return null rather than read it.
 * Deliberately not an `#[ORM\Entity]` (never mapped/persisted).
 */
class UninitializedIdEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private int $id;
}
