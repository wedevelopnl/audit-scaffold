<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Fixtures\Subject;

/**
 * Plain object whose identifier is exposed through an initialized `id` property.
 */
class PropertyIdObject
{
    private int $id;

    public function __construct(int $id = 42)
    {
        $this->id = $id;
    }
}
