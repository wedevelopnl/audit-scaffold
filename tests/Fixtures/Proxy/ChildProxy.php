<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Fixtures\Proxy;

use Doctrine\Persistence\Proxy;

/**
 * Stands in for a Doctrine proxy: implements Proxy and extends a concrete parent,
 * so SubjectHelper::getSubjectClass() unwinds it to ProxyParent.
 *
 * @template-implements Proxy<ProxyParent>
 */
class ChildProxy extends ProxyParent implements Proxy
{
    public function __load(): void {}

    public function __isInitialized(): bool
    {
        return true;
    }
}
