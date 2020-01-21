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

use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Database\Driver\Mysql as CakeMysql;
use Cake\Database\Driver\Sqlserver;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Exception\MissingDatasourceConfigException;
use DatabaseBackup\BackupTrait;
use DatabaseBackup\Driver\Mysql;
use DatabaseBackup\TestSuite\TestCase;
use InvalidArgumentException;

/**
 * BackupTraitTest class
 */
class BackupTraitTest extends TestCase
{
    use BackupTrait;

    /**
     * @var bool
     */
    public $autoFixtures = false;

    /**
     * Fixtures
     * @var array
     */
    public $fixtures = [
        'core.Articles',
        'core.Comments',
    ];

    /**
     * Test for `getAbsolutePath()` method
     * @test
     */
    public function testGetAbsolutePath()
    {
        $this->assertEquals('/file.txt', $this->getAbsolutePath('/file.txt'));
        $this->assertEquals(Configure::read('DatabaseBackup.target') . DS . 'file.txt', $this->getAbsolutePath('file.txt'));
        $expected = Configure::read('DatabaseBackup.target') . DS . 'file.txt';
        $this->assertEquals($expected, $this->getAbsolutePath(Configure::read('DatabaseBackup.target') . DS . 'file.txt'));
    }

    /**
     * Test for `getCompression()` method
     * @test
     */
    public function testGetCompression()
    {
        foreach ([
            'backup.sql' => false,
            'backup.sql.bz2' => 'bzip2',
            'backup.sql.gz' => 'gzip',
            'text.txt' => null,
        ] as $filename => $expectedCompression) {
            $this->assertEquals($expectedCompression, $this->getCompression($filename));
        }
    }

    /**
     * Test for `getConnection()` method
     * @test
     */
    public function testGetConnection()
    {
        ConnectionManager::setConfig('fake', ['url' => 'mysql://root:password@localhost/my_database']);

        foreach ([
            null,
            Configure::read('DatabaseBackup.connection'),
            'fake',
        ] as $name) {
            $connection = $this->getConnection($name);
            $this->assertInstanceof(Connection::class, $connection);
            $this->assertInstanceof(CakeMysql::class, $connection->getDriver());
        }

        //With an invalid connection
        $this->expectException(MissingDatasourceConfigException::class);
        $this->expectExceptionMessage('The datasource configuration "noExisting" was not found');
        $this->getConnection('noExisting');
    }

    /**
     * Test for `getDriver()` method
     * @test
     */
    public function testGetDriver()
    {
        foreach ([ConnectionManager::get('test'), null] as $driver) {
            $this->assertInstanceof(Mysql::class, $this->getDriver($driver));
        }

        //With a no existing driver
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The `Sqlserver` driver does not exist');
        $connection = $this->getMockBuilder(get_class($this->getConnection()))
            ->setMethods(['getDriver'])
            ->setConstructorArgs([$this->getConnection()->config()])
            ->getMock();
        $connection->method('getDriver')->will($this->returnValue(new Sqlserver()));
        $this->getDriver($connection);
    }

    /**
     * Test for `getExtension()` method
     * @test
     */
    public function testGetExtension()
    {
        foreach ([
            'backup.sql' => 'sql',
            'backup.sql.bz2' => 'sql.bz2',
            'backup.sql.gz' => 'sql.gz',
            'backup.SQL' => 'sql',
            'backup.SQL.BZ2' => 'sql.bz2',
            'backup.SQL.GZ' => 'sql.gz',
            'text.txt' => null,
            'text' => null,
            '.txt' => null,
        ] as $filename => $expectedExtension) {
            $this->assertEquals($expectedExtension, $this->getExtension($filename));
        }
    }

    /**
     * Test for `getValidCompressions()` method
     * @test
     */
    public function testGetValidCompressions()
    {
        $this->assertNotEmpty($this->getValidCompressions());
    }
}
