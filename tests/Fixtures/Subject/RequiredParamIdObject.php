<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Fixtures\Subject;

/**
 * getId() requires an argument, so SubjectHelper::getIdMethod() must refuse to
 * invoke it and return null.
 */
class RequiredParamIdObject
{
    public function getId(int $discriminator): int
    {
        return $discriminator;
    }
}
