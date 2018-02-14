<?php
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
namespace DatabaseBackup\Test\TestCase\Driver;

use Cake\Core\Configure;
use DatabaseBackup\BackupTrait;
use DatabaseBackup\Driver\Sqlite;
use DatabaseBackup\TestSuite\DriverTestCase;
use Reflection\ReflectionTrait;

/**
 * SqliteTest class
 */
class SqliteTest extends DriverTestCase
{
    use BackupTrait;
    use ReflectionTrait;

    /**
     * @var \DatabaseBackup\Driver\Sqlite
     */
    protected $Driver;

    /**
     * Fixtures
     * @var array
     */
    public $fixtures = [
        'plugin.database_backup.Sqlite/Articles',
        'plugin.database_backup.Sqlite/Comments',
    ];

    /**
     * Setup the test case, backup the static object values so they can be
     * restored. Specifically backs up the contents of Configure and paths in
     *  App if they have not already been backed up
     * @return void
     */
    public function setUp()
    {
        Configure::write(DATABASE_BACKUP . '.connection', 'test_sqlite');

        $this->Driver = new Sqlite($this->getConnection());

        parent::setUp();
    }

    /**
     * Test for `_exportExecutable()` method
     * @test
     */
    public function testExportExecutable()
    {
        $expected = $this->getBinary('sqlite3') . ' /tmp/example.sq3 .dump';
        $result = $this->invokeMethod($this->Driver, '_exportExecutable');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test for `_importExecutable()` method
     * @test
     */
    public function testImportExecutable()
    {
        $expected = $this->getBinary('sqlite3') . ' /tmp/example.sq3';
        $result = $this->invokeMethod($this->Driver, '_importExecutable');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test for `export()` method on failure
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Failed with exit code `1`
     * @test
     */
    public function testExportOnFailure()
    {
        $this->Driver = $this->getMockBuilder(Sqlite::class)
            ->setMethods(['_exportExecutableWithCompression'])
            ->setConstructorArgs([$this->getConnection()])
            ->getMock();

        $this->Driver->method('_exportExecutableWithCompression')
             ->will($this->returnCallback(function () {
                $config = $this->getProperty($this->Driver, 'config');

                return sprintf(
                    '%s %s .dump noExistingDir/dump.sql' . REDIRECT_TO_DEV_NULL,
                    $this->getBinary('sqlite3'),
                    $config['database']
                );
             }));

        $this->Driver->export($this->getAbsolutePath('example.sql'));
    }

    /**
     * Test for `import()` method
     * @test
     */
    public function testImport()
    {
        $this->loadFixtures();

        parent::testImport();
    }

    /**
     * Test for `import()` method on failure
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Failed with exit code `1`
     * @test
     */
    public function testImportOnFailure()
    {
        $this->Driver = $this->getMockBuilder(Sqlite::class)
            ->setMethods(['_importExecutableWithCompression', 'beforeImport'])
            ->setConstructorArgs([$this->getConnection()])
            ->getMock();

        $this->Driver->method('beforeImport')
            ->will($this->returnValue(true));

        $this->Driver->method('_importExecutableWithCompression')
             ->will($this->returnCallback(function () {
                $config = $this->getProperty($this->Driver, 'config');

                return sprintf(
                    '%s %s .dump noExisting'. REDIRECT_TO_DEV_NULL,
                    $this->getBinary('sqlite3'),
                    $config['database']
                );
             }));

        $this->Driver->import('noExistingFile');
    }
}
