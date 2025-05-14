<?php
declare(strict_types=1);

namespace App\Utility;

use App\Database\FakeConnection;
use Cake\Datasource\ConnectionInterface;
use DatabaseBackup\Utility\AbstractBackupUtility;

/**
 * A fake Utility per tests.
 */
class FakeBackupUtility extends AbstractBackupUtility
{
    public function __construct(string|ConnectionInterface|null $Connection = null)
    {
        $Connection = $Connection ?: new FakeConnection();

        parent::__construct($Connection);
    }

    public function filename(string $filename): self
    {
        return $this;
    }
}
