<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Fixtures\Subject;

/**
 * Carries no identifier of any kind and is not stringable, so it doubles as the
 * "no identifier" case (getObjectIdentifier returns null) and the "cannot convert
 * to string" case (identifierToString throws).
 */
class PlainObject {}
