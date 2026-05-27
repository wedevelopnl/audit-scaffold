<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Fixtures\Subject;

/**
 * Identifier that SubjectHelper::identifierToString() serialises via json_encode().
 */
class JsonSerializableObject implements \JsonSerializable
{
    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return ['type' => 'fixture', 'id' => 7];
    }
}
