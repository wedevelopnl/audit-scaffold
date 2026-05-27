<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Unit\Util;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WeDevelop\Audit\Tests\Fixtures\Subject\StringableObject;
use WeDevelop\Audit\Util\TranslationHelper;

final class TranslationHelperTest extends TestCase
{
    /** @param string|list<string> $namespace */
    #[DataProvider('namespaceCases')]
    public function testInNamespace(string $expected, string $message, string|array $namespace): void
    {
        self::assertSame($expected, TranslationHelper::inNamespace($message, $namespace));
    }

    /** @return iterable<string, array{string, string, string|list<string>}> */
    public static function namespaceCases(): iterable
    {
        yield 'string namespace is prepended' => ['action.user.login', 'user.login', 'action'];
        yield 'already-prefixed message is returned as-is' => ['action.user.login', 'action.user.login', 'action'];
        yield 'array namespace is joined with dots' => ['info.user.mfa.method', 'method', ['info', 'user.mfa']];
        yield 'leading dot on the message is trimmed' => ['action.user.login', '.user.login', 'action'];
        yield 'surrounding dots on the namespace are trimmed' => ['action.user.login', 'user.login', '.action.'];
    }

    /**
     * @param array<string, string> $expected
     * @param iterable<int|string, null|scalar|\Stringable> $parameters
     */
    #[DataProvider('parameterCases')]
    public function testPrepareTranslationParameters(array $expected, iterable $parameters): void
    {
        self::assertSame($expected, TranslationHelper::prepareTranslationParameters($parameters));
    }

    /** @return iterable<string, array{array<string, string>, iterable<int|string, null|scalar|\Stringable>}> */
    public static function parameterCases(): iterable
    {
        yield 'wraps keys and stringifies values' => [
            ['%user%' => 'alice', '%count%' => '3'],
            ['user' => 'alice', 'count' => 3],
        ];
        yield 'already-wrapped keys are left intact' => [
            ['%user%' => 'bob'],
            ['%user%' => 'bob'],
        ];
        yield 'accepts an iterator and stringable values' => [
            ['%name%' => 'totp'],
            new \ArrayIterator(['name' => new StringableObject('totp')]),
        ];
    }
}
