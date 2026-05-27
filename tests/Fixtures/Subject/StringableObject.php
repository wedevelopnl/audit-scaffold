<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Fixtures\Subject;

/**
 * Identifier that converts to a string via Stringable.
 */
class StringableObject implements \Stringable
{
    public function __construct(private string $value = 'stringable-id') {}

    public function __toString(): string
    {
        return $this->value;
    }
}
