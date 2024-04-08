<?php declare(strict_types=1);

namespace WeDevelop\Audit\Util;

class TranslationHelper
{
    /** @param string|list<string> $namespace */
    public static function inNamespace(string $message, string|array $namespace): string
    {
        $namespace = implode('.', array_map(static fn (string $namespace): string => trim($namespace, '.'), (array) $namespace));
        $message = ltrim($message, '.');
        return !str_starts_with($message, $namespace) ? $namespace . '.' . $message : $message;
    }

    /**
     * @param iterable<string, null|scalar|\Stringable> $parameters
     * @return array<string, string>
     */
    public static function prepareTranslationParameters(iterable $parameters): array
    {
        if (!is_array($parameters)) {
            $parameters = iterator_to_array($parameters);
        }

        return array_combine(
            array_map(static fn (int|string $key): string => '%' . trim((string) $key, '%') . '%', array_keys($parameters)),
            array_map(static fn (mixed $value): string => (string) $value, array_values($parameters)),
        );
    }
}
