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
    public array $fixtures = [
        'core.Articles',
        'core.Comments',
    ];

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->Trait ??= new BackupTraitAsClass();
    }

    /**
     * @test
     * @uses \DatabaseBackup\BackupTrait::getAbsolutePath()
     */
    public function testGetAbsolutePath(): void
    {
        $expected = Configure::readOrFail('DatabaseBackup.target') . 'file.txt';
        $this->assertSame($expected, $this->Trait->getAbsolutePath('file.txt'));
        $this->assertSame($expected, $this->Trait->getAbsolutePath(Configure::read('DatabaseBackup.target') . 'file.txt'));
    }

    /**
     * @test
     * @uses \DatabaseBackup\BackupTrait::getCompression()
     */
    public function testGetCompression(): void
    {
        foreach (
            [
                'backup.sql' => null,
                'backup.sql.bz2' => 'bzip2',
                DS . 'backup.sql.bz2' => 'bzip2',
                Configure::read('DatabaseBackup.target') . 'backup.sql.bz2' => 'bzip2',
                'backup.sql.gz' => 'gzip',
                'text.txt' => null,
            ] as $filename => $expectedCompression
        ) {
            $this->assertSame($expectedCompression, $this->Trait->getCompression($filename));
        }
    }

    /**
     * @test
     * @uses \DatabaseBackup\BackupTrait::getConnection()
     */
    public function testGetConnection(): void
    {
        foreach ([null, Configure::read('DatabaseBackup.connection')] as $name) {
            $connection = $this->Trait->getConnection($name);
            $this->assertInstanceof(Connection::class, $connection);
            $this->assertSame('test', $connection->config()['name']);
        }

        ConnectionManager::setConfig('fake', ['url' => 'mysql://root:password@localhost/my_database']);
        $connection = $this->Trait->getConnection('fake');
        $this->assertInstanceof(Connection::class, $connection);
        $this->assertSame('fake', $connection->config()['name']);

        $this->expectException(MissingDatasourceConfigException::class);
        $this->getConnection('noExisting');
    }

    /**
     * @test
     * @uses \DatabaseBackup\BackupTrait::getExtension()
     */
    public function testGetExtension(): void
    {
        foreach (
            [
                'backup.sql' => 'sql',
                'backup.sql.bz2' => 'sql.bz2',
                DS . 'backup.sql.bz2' => 'sql.bz2',
                Configure::read('DatabaseBackup.target') . 'backup.sql.bz2' => 'sql.bz2',
                'backup.sql.gz' => 'sql.gz',
                'backup.SQL' => 'sql',
                'backup.SQL.BZ2' => 'sql.bz2',
                'backup.SQL.GZ' => 'sql.gz',
                'text.txt' => null,
                'text' => null,
                '.txt' => null,
            ] as $filename => $expectedExtension
        ) {
            $this->assertSame($expectedExtension, $this->Trait->getExtension($filename));
        }
    }

    /**
     * @test
     * @uses \DatabaseBackup\BackupTrait::getValidCompressions()
     */
    public function testGetValidCompressions(): void
    {
        $this->assertNotEmpty($this->Trait->getValidCompressions());
    }
}
