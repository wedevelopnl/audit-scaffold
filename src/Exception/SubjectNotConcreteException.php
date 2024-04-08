<?php declare(strict_types=1);

namespace WeDevelop\Audit\Exception;

class SubjectNotConcreteException extends \RuntimeException implements AuditExceptionInterface
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct(
            'Subject not concrete (anonymous classes cannot be referenced in audit logs)',
            0,
            $previous,
        );
    }
}
