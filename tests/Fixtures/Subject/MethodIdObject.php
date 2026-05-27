<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Fixtures\Subject;

/**
 * Exposes its identifier through a parameterless getId() method.
 */
class MethodIdObject
{
    public function __construct(private int $value = 99) {}

    public function getId(): int
    {
        return $this->value;
    }
}
