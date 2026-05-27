<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Fixtures\Proxy;

use Doctrine\Persistence\Proxy;

/**
 * A proxy with no parent class, so the manual proxy-unwind in
 * SubjectHelper::getSubjectClass() cannot resolve a concrete class and throws.
 *
 * @template-implements Proxy<object>
 */
class OrphanProxy implements Proxy
{
    public function __load(): void {}

    public function __isInitialized(): bool
    {
        return true;
    }
}
