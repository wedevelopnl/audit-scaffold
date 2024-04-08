<?php declare(strict_types=1);

namespace WeDevelop\Audit\Util;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Proxy;
use WeDevelop\Audit\Exception\IdentifierStringConversionException;
use WeDevelop\Audit\Exception\SubjectNotConcreteException;

class SubjectHelper
{
    public const JSON_OPTIONS = \JSON_UNESCAPED_SLASHES | \JSON_PRESERVE_ZERO_FRACTION;

    private static ?ManagerRegistry $registry = null;

    public static function setManagerRegistry(ManagerRegistry $registry): void
    {
        self::$registry = $registry;
    }

    /**
     * @param object|class-string $subject
     * @throws SubjectNotConcreteException
     */
    public static function assertObjectConcrete(object|string $subject): void
    {
        if ((new \ReflectionClass($subject))->isAnonymous()) {
            throw new SubjectNotConcreteException();
        }
    }

    /** @return class-string */
    public static function getSubjectClass(object $subject): string
    {
        $class = get_class($subject);

        // Unwind Doctrine entity proxies.
        try {
            if (null !== $entityClass = self::$registry?->getManagerForClass($class)?->getClassMetadata($class)?->getName()) {
                $class = $entityClass;
            }
        } catch (\Exception) {
        }
        // Registry not available, do it manually and hope it works correctly.
        while (is_a($class, Proxy::class, true)) {
            if (false === $parentClass = (new \ReflectionClass($class))->getParentClass()) {
                throw new SubjectNotConcreteException();
            }
            $class = $parentClass->getName();
        }

        return $class;
    }

    /** @return mixed|array<string, mixed>|null */
    public static function getObjectIdentifier(object $subject): mixed
    {
        $reflection = new \ReflectionClass($subject);
        return self::getDoctrineProperties($subject)
            ?? self::getIdAttributes($reflection, $subject)
            ?? self::getIdProperty($reflection, $subject)
            ?? self::getIdMethod($reflection, $subject);
    }

    public static function identifierToString(mixed $identifier): ?string
    {
        if (is_null($identifier)) {
            return null;
        }
        if (is_bool($identifier)) {
            return $identifier ? 'true' : 'false';
        }
        if (is_scalar($identifier)
            || (is_object($identifier) && ($identifier instanceof \Stringable || method_exists($identifier, '__toString')))
        ) {
            return (string) $identifier;
        }
        if (is_array($identifier) || $identifier instanceof \JsonSerializable) {
            try {
                return \json_encode($identifier, \JSON_THROW_ON_ERROR | self::JSON_OPTIONS);
            } catch (\JsonException $e) {
                throw new IdentifierStringConversionException($identifier, $e);
            }
        }
        throw new IdentifierStringConversionException($identifier);
    }

    /** @return mixed|array<string, mixed>|null */
    public static function getDoctrineProperties(object $subject): mixed
    {
        $class = get_class($subject);
        try {
            $ids = self::$registry?->getManagerForClass($class)?->getClassMetadata($class)?->getIdentifierValues($subject);
            if (is_array($ids) && 1 === count($ids)) {
                return $ids[array_key_first($ids)];
            }
            return $ids;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param \ReflectionClass<object> $reflection
     * @return mixed|array<string, mixed>|null
     */
    public static function getIdAttributes(\ReflectionClass $reflection, object $subject): mixed
    {
        $idProperties = array_filter(
            $reflection->getProperties(),
            static fn (\ReflectionProperty $property): bool => count($property->getAttributes(ORM\Id::class)) > 0
                || count($property->getAttributes(ODM\Id::class)) > 0,
        );
        if (1 === count($idProperties)) {
            $property = $idProperties[array_key_first($idProperties)];
            if ($property->isInitialized($subject)) {
                $property->setAccessible(true);
                return $property->getValue($subject);
            }
        } elseif (1 < count($idProperties)) {
            $areInitialized = array_reduce($idProperties, static function (bool $carry, \ReflectionProperty $property) use ($subject): bool {
                return $carry && $property->isInitialized($subject);
            }, true);
            if ($areInitialized) {
                ksort($idProperties);
                return array_combine(
                    array_map(static fn (\ReflectionProperty $property): string => $property->getName(), $idProperties),
                    array_map(static function (\ReflectionProperty $property) use ($subject): mixed {
                        $property->setAccessible(true);
                        return $property->getValue($subject);
                    }, $idProperties),
                );
            }
        }
        return null;
    }

    /** @param \ReflectionClass<object> $reflection */
    public static function getIdProperty(\ReflectionClass $reflection, object $subject): mixed
    {
        if ($reflection->hasProperty('id')) {
            $property = $reflection->getProperty('id');
            if ($property->isInitialized($subject)) {
                $property->setAccessible(true);
                return $property->getValue($subject);
            }
        }
        return null;
    }

    /** @param \ReflectionClass<object> $reflection */
    public static function getIdMethod(\ReflectionClass $reflection, object $subject): mixed
    {
        if ($reflection->hasMethod('getId')) {
            $method = $reflection->getMethod('getId');
            if ($method->getNumberOfRequiredParameters() > 0) {
                return null;
            }
            $method->setAccessible(true);
            return $method->invoke($subject);
        }
        return null;
    }
}
