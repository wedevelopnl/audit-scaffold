<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Integration\Util;

use WeDevelop\Audit\Tests\Fixtures\Doctrine\CompositeIdEntity;
use WeDevelop\Audit\Tests\Fixtures\Doctrine\IntegrationEntity;
use WeDevelop\Audit\Tests\Support\DoctrineTestCase;
use WeDevelop\Audit\Util\SubjectHelper;

final class SubjectHelperDoctrineTest extends DoctrineTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema([IntegrationEntity::class, CompositeIdEntity::class]);
        SubjectHelper::setManagerRegistry($this->registryReturningEntityManager());
    }

    public function testReadsSingleIdentifierFromDoctrineMetadata(): void
    {
        $entity = new IntegrationEntity(5);
        $this->em->persist($entity);
        $this->em->flush();

        self::assertSame(5, SubjectHelper::getObjectIdentifier($entity));
    }

    public function testReadsCompositeIdentifierFromDoctrineMetadata(): void
    {
        $entity = new CompositeIdEntity(1, 'abc');
        $this->em->persist($entity);
        $this->em->flush();

        self::assertSame(['first' => 1, 'second' => 'abc'], SubjectHelper::getObjectIdentifier($entity));
    }

    public function testResolvesConcreteClassFromADoctrineProxy(): void
    {
        $this->em->persist(new IntegrationEntity(5));
        $this->em->flush();
        $this->em->clear();

        $proxy = $this->em->getReference(IntegrationEntity::class, 5);
        self::assertNotNull($proxy);

        self::assertSame(IntegrationEntity::class, SubjectHelper::getSubjectClass($proxy));
    }
}
