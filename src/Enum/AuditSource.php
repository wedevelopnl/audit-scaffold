<?php declare(strict_types=1);

namespace WeDevelop\Audit\Enum;

enum AuditSource: string
{
    case CONSOLE = 'console';
    case UI = 'ui';
    case API = 'api';
    case WEBHOOK = 'webhook';
    case JOB = 'job';
    case UNKNOWN = 'unknown';
}
