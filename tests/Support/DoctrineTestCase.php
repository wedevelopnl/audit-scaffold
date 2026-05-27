<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Support;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Boots a real Doctrine ORM EntityManager backed by in-memory SQLite, so the
 * integration tests exercise genuine metadata, identifier extraction and proxy
 * behaviour. Uses only APIs shared by ORM 2.x and 3.x (the constructor rather
 * than the removed EntityManager::create(), and a params-array DBAL connection)
 * so the same bootstrap runs on both CI dependency sets.
 */
abstract class DoctrineTestCase extends TestCase
{
    use ResetsSubjectHelperRegistry;
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration(
            [dirname(__DIR__) . '/Fixtures/Doctrine', dirname(__DIR__) . '/Fixtures/Audit'],
            true,
            null,
            new ArrayAdapter(),
        );

        // On PHP 8.4+, ORM 3 with Symfony 8's var-exporter (which dropped
        // LazyGhostTrait) needs native lazy objects for proxies. Enable them where
        // available; on PHP 8.2/8.3 the Symfony 7 LazyGhost and ORM 2.x's own proxy
        // mechanism are used instead, so the same bootstrap covers every CI cell.
        if (\PHP_VERSION_ID >= 80400 && method_exists($config, 'enableNativeLazyObjects')) {
            $config->enableNativeLazyObjects(true);
        }

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $this->em = new EntityManager($connection, $config);
    }

    protected function tearDown(): void
    {
        $this->resetSubjectHelperRegistry();
    }

    /** @param list<class-string> $classes */
    protected function createSchema(array $classes): void
    {
        $metadata = array_map(
            fn (string $class): object => $this->em->getClassMetadata($class),
            $classes,
        );
        (new SchemaTool($this->em))->createSchema($metadata);
    }

    /** A ManagerRegistry that routes every class to the test EntityManager. */
    protected function registryReturningEntityManager(): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->em);
        $registry->method('getManager')->willReturn($this->em);
        $registry->method('getRepository')->willReturnCallback(
            fn (string $class): object => $this->em->getRepository($class),
        );

        return $registry;
    }
}
