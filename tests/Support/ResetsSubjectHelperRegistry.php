<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Support;

use WeDevelop\Audit\Util\SubjectHelper;

/**
 * SubjectHelper keeps its ManagerRegistry in a mutable static with no public
 * reset, so any test that sets one must clear it afterwards to avoid leaking
 * into later tests. The production API is intentionally left untouched.
 */
trait ResetsSubjectHelperRegistry
{
    protected function resetSubjectHelperRegistry(): void
    {
        (new \ReflectionProperty(SubjectHelper::class, 'registry'))->setValue(null, null);
    }
}
