<?php
declare(strict_types=1);

namespace DatabaseBackup;

/**
 * OperationType.
 *
 * @since 2.13.5
 */
enum OperationType: string
{
    case Export = 'export';

    case Import = 'import';
}
