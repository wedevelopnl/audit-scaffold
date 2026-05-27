<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Fixtures\Subject;

/**
 * Has an `id` property that is never initialized, so getIdProperty() returns null.
 */
class UninitializedPropertyIdObject
{
    private int $id;
}
