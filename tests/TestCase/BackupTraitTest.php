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
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;

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

    public static function getAbsolutePathProvider(): Generator
    {
        yield [
            Configure::readOrFail('DatabaseBackup.target') . 'file.txt',
            'file.txt',
        ];

        yield [
            Configure::readOrFail('DatabaseBackup.target') . 'file.txt',
            Configure::readOrFail('DatabaseBackup.target') . 'file.txt',
        ];

        yield [
            TMP . 'tmp_file',
            TMP . 'tmp_file',
        ];
    }

    /**
     * @uses \DatabaseBackup\BackupTrait::getAbsolutePath()
     */
    #[Test]
    #[DataProvider('getAbsolutePathProvider')]
    public function testGetAbsolutePath(string $expectedAbsolutePath, string $path): void
    {
        $result = $this->Trait->getAbsolutePath($path);
        $this->assertSame($expectedAbsolutePath, $result);
    }

    /**
     * @uses \DatabaseBackup\BackupTrait::getAbsolutePath()
     */
    #[Test]
    #[WithoutErrorHandler]
    public function testGetAbsolutePathIsDeprecated(): void
    {
        $this->deprecated(function (): void {
            $this->Trait->getAbsolutePath(TMP . 'tmp_file');
        });
    }

    /**
     * @test
     * @uses \DatabaseBackup\BackupTrait::getCompression()
     */
    #[Test]
    #[TestWith([null, 'backup.sql'])]
    #[TestWith(['bzip2', 'backup.sql.bz2'])]
    #[TestWith(['bzip2', DS . 'backup.sql.bz2'])]
    #[TestWith(['bzip2', TMP . 'backup.sql.bz2'])]
    #[TestWith(['gzip', 'backup.sql.gz'])]
    #[TestWith([null, 'text.txt'])]
    public function testGetCompression(?string $expectedCompression, string $filename): void
    {
        $this->assertSame($expectedCompression, $this->Trait->getCompression($filename));
    }

    /**
     * @uses \DatabaseBackup\BackupTrait::getCompression()
     */
    #[Test]
    #[WithoutErrorHandler]
    public function testGetCompressionIsDeprecated(): void
    {
        $this->deprecated(function (): void {
            $this->Trait->getCompression('backup.sql');
        });
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

    /**
     * @test
     * @uses \DatabaseBackup\BackupTrait::getExtension()
     */
    #[Test]
    #[TestWith(['sql', 'backup.sql'])]
    #[TestWith(['sql.bz2', 'backup.sql.bz2'])]
    #[TestWith(['sql.bz2', DS . 'backup.sql.bz2'])]
    #[TestWith(['sql.bz2', TMP . 'backup.sql.bz2'])]
    #[TestWith(['sql.gz', 'backup.sql.gz'])]
    #[TestWith(['sql', 'backup.SQL'])]
    #[TestWith(['sql.bz2', 'backup.SQL.BZ2'])]
    #[TestWith(['sql.gz', 'backup.SQL.GZ'])]
    #[TestWith([null, 'text.txt'])]
    #[TestWith([null, 'text'])]
    #[TestWith([null, '.txt'])]
    public function testGetExtension(?string $expectedExtension, string $filename): void
    {
        $this->assertSame($expectedExtension, $this->Trait->getExtension($filename));
    }

    /**
     * @uses \DatabaseBackup\BackupTrait::getExtension()
     */
    #[Test]
    #[WithoutErrorHandler]
    public function testGetExtensionIsDeprecated(): void
    {
        $this->deprecated(function (): void {
            $this->Trait->getExtension('backup.sql');
        });
    }

    /**
     * @uses \DatabaseBackup\BackupTrait::getValidCompressions()
     */
    #[Test]
    public function testGetValidCompressions(): void
    {
        $this->assertNotEmpty($this->Trait->getValidCompressions());
    }

    /**
     * @uses \DatabaseBackup\BackupTrait::getValidCompressions()
     */
    #[Test]
    #[WithoutErrorHandler]
    public function testGetValidCompressionsIsDeprecated(): void
    {
        $this->deprecated(function (): void {
            $this->Trait->getValidCompressions();
        });
    }
}
