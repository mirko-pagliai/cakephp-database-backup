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

namespace App\Executor;

use App\Database\FakeConnection;
use DatabaseBackup\Executor\Executor;
use DatabaseBackup\OperationType;

/**
 * A fake `Executor` for tests.
 */
class FakeExecutor extends Executor
{
    public function __construct(OperationType $OperationType = OperationType::Export)
    {
        parent::__construct(new FakeConnection(), $OperationType);
    }

    public function getBinaryName(): string
    {
        return $this->OperationType->value . '-binary';
    }
}
