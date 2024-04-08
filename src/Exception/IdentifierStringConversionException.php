<?php declare(strict_types=1);

namespace WeDevelop\Audit\Exception;

class IdentifierStringConversionException extends \RuntimeException implements AuditExceptionInterface
{
    public function __construct(private readonly mixed $identifier, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf(
            'Cannot convert identifier of type "%s" to a string',
            get_debug_type($this->identifier),
        ), 0, $previous);
    }
}
