<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Integration\ValueObject;

use Symfony\Component\HttpFoundation\Request;
use WeDevelop\Audit\Security\AuditImpersonationToken;
use WeDevelop\Audit\Security\AuditToken;
use WeDevelop\Audit\Tests\Fixtures\Doctrine\UserEntity;
use WeDevelop\Audit\Tests\Support\DoctrineTestCase;
use WeDevelop\Audit\ValueObject\Context;

final class ContextRefDbTest extends DoctrineTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema([UserEntity::class]);
    }

    public function testRefDbLeavesManagedUsersUntouched(): void
    {
        $user = new UserEntity(1, 'alice');
        $this->em->persist($user);
        $this->em->flush();

        $context = $this->contextFor($user);

        self::assertSame($user, $context->refDb($this->em)->token?->getUser());
    }

    public function testRefDbReplacesDetachedUsersWithManagedReferences(): void
    {
        $user = new UserEntity(1, 'alice');
        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $resolved = $this->contextFor($user)->refDb($this->em)->token?->getUser();

        self::assertInstanceOf(UserEntity::class, $resolved);
        self::assertNotSame($user, $resolved);
        self::assertTrue($this->em->contains($resolved));
        self::assertSame(1, $resolved->getId());
    }

    public function testRefDbResolvesBothSidesOfAnImpersonationToken(): void
    {
        $user = new UserEntity(1, 'alice');
        $admin = new UserEntity(2, 'admin');
        $this->em->persist($user);
        $this->em->persist($admin);
        $this->em->flush();

        $token = new AuditImpersonationToken($user, new AuditToken($admin));
        $context = Context::ui(Request::create('/', server: ['REMOTE_ADDR' => '192.0.2.1']), $token);

        $refreshed = $context->refDb($this->em)->token;

        self::assertInstanceOf(AuditImpersonationToken::class, $refreshed);
        self::assertSame($user, $refreshed->getUser());
        self::assertSame($admin, $refreshed->getOriginalToken()->getUser());
    }

    private function contextFor(UserEntity $user): Context
    {
        return Context::ui(Request::create('/', server: ['REMOTE_ADDR' => '192.0.2.1']), new AuditToken($user));
    }
}
