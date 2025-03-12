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

namespace DatabaseBackup\Test\TestCase;

use App\BackupTraitAsClass;
use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Exception\MissingDatasourceConfigException;
use DatabaseBackup\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * BackupTraitTest class.
 *
 * @uses \DatabaseBackup\BackupTrait
 */
class BackupTraitTest extends TestCase
{
    /**
     * @var \App\BackupTraitAsClass
     */
    protected BackupTraitAsClass $Trait;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->Trait ??= new BackupTraitAsClass();
    }

    /**
     * @uses \DatabaseBackup\BackupTrait::getConnection()
     */
    #[Test]
    #[TestWith([''])]
    #[TestWith(['test'])]
    #[TestWith(['fake'])]
    public function testGetConnection(string $connectionName): void
    {
        if ($connectionName == 'fake') {
            ConnectionManager::setConfig('fake', ['url' => 'mysql://root:password@localhost/my_database']);
        }

        $Connection = $this->Trait->getConnection($connectionName);
        $this->assertInstanceof(Connection::class, $Connection);
        $this->assertSame($connectionName ?: Configure::read('DatabaseBackup.connection'), $Connection->configName());
    }

    /**
     * @uses \DatabaseBackup\BackupTrait::getConnection()
     */
    #[Test]
    public function testGetConnectionWithNoExistingConnection(): void
    {
        $this->expectException(MissingDatasourceConfigException::class);
        $this->Trait->getConnection('noExisting');
    }
}
