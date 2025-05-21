<?php
declare(strict_types=1);

/**
 * This file is part of cakephp-database-backup.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright   Copyright (c) Mirko Pagliai
 * @link        https://github.com/mirko-pagliai/cakephp-database-backup
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace DatabaseBackup\Executor;

use DatabaseBackup\OperationType;
use Override;

/**
 * PostgresExecutor to export/import database backups.
 */
class PostgresExecutor extends Executor
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function getBinaryName(): string
    {
        return $this->OperationType == OperationType::Export ? 'pg_dump' : 'pg_restore';
    }
}
