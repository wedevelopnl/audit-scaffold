<?php declare(strict_types=1);

namespace WeDevelop\Audit;

use WeDevelop\Audit\Entity\Subject;
use WeDevelop\Audit\ValueObject\Context;

interface AuditDataInterface
{
    public function getLoggedAt(): \DateTimeImmutable;

    public function getContext(): Context;

    public function getSubject(): ?Subject;

    /**
     * While a non-nullable array is type-hinted during audit log construction
     * (in static, named constructors), the audit log must continue to work if
     * this data is removed from the audit log entity/document (for example, in
     * the case of a GDPR data-removal).
     *
     * @return array<string, mixed>|null
     */
    public function getData(): ?array;
}
