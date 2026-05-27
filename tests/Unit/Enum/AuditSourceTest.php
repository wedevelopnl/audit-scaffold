<?php declare(strict_types=1);

namespace WeDevelop\Audit\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use WeDevelop\Audit\Enum\AuditSource;

final class AuditSourceTest extends TestCase
{
    public function testBackingValues(): void
    {
        self::assertSame('console', AuditSource::CONSOLE->value);
        self::assertSame('ui', AuditSource::UI->value);
        self::assertSame('api', AuditSource::API->value);
        self::assertSame('webhook', AuditSource::WEBHOOK->value);
        self::assertSame('job', AuditSource::JOB->value);
        self::assertSame('unknown', AuditSource::UNKNOWN->value);
    }

    public function testHasExactlySixCases(): void
    {
        self::assertCount(6, AuditSource::cases());
    }
}
