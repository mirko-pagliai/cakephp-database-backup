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
 * @since       2.0.0
 */
namespace DatabaseBackup\TestSuite;

use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Event\EventList;
use DatabaseBackup\TestSuite\TestCase;
use ErrorException;

/**
 * DriverTestCase class.
 *
 * Classes with tests for driver must extend this class.
 */
abstract class DriverTestCase extends TestCase
{
    /**
     * `Articles` table
     * @var \Cake\ORM\Table
     */
    protected $Articles;

    /**
     * `Comments` table
     * @var \Cake\ORM\Table
     */
    protected $Comments;

    /**
     * `Driver` instance
     * @var \DatabaseBackup\Driver\Driver
     */
    protected $Driver;

    /**
     * Driver class
     * @since 2.5.1
     * @var string
     */
    protected $DriverClass;

    /**
     * Auto fixtures
     * @var bool
     */
    public $autoFixtures = false;

    /**
     * Name of the database connection
     * @var string
     */
    protected $connection;

    /**
     * Fixtures
     * @var array
     */
    public $fixtures;

    /**
     * Called before every test method
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Configure::write('DatabaseBackup.connection', $this->connection);
        $connection = $this->getConnection();
        $this->Articles = $this->getTable('Articles', compact('connection'));
        $this->Comments = $this->getTable('Comments', compact('connection'));

        //Enable event tracking
        $this->Driver = new $this->DriverClass($connection);
        $this->Driver->getEventManager()->setEventList(new EventList());
    }

    /**
     * Internal method to get all records from the database
     * @return array
     */
    final protected function getAllRecords(): array
    {
        foreach (['Articles', 'Comments'] as $name) {
            $records[$name] = $this->$name->find()->enableHydration(false)->toArray();
        }

        return $records;
    }

    /**
     * Internal method to mock a driver
     * @param array $methods The list of methods to mock
     * @return \MockBuilder
     * @since 2.6.1
     * @uses $Driver
     */
    final protected function getMockForDriver(array $methods): object
    {
        return $this->getMockBuilder(get_class($this->Driver))
            ->setMethods($methods)
            ->setConstructorArgs([$this->getConnection()])
            ->getMock();
    }

    /**
     * Test for `__construct()` method
     * @return void
     * @test
     */
    public function testConstruct()
    {
        $this->assertInstanceof(Connection::class, $this->getProperty($this->Driver, 'connection'));
    }

    /**
     * Test for `export()` method
     * @return void
     * @test
     */
    public function testExport()
    {
        $backup = $this->getAbsolutePath('example.sql');
        $this->assertTrue($this->Driver->export($backup));
        $this->assertFileExists($backup);
        $this->assertEventFired('Backup.beforeExport', $this->Driver->getEventManager());
        $this->assertEventFired('Backup.afterExport', $this->Driver->getEventManager());
    }

    /**
     * Test for `export()` and `import()` methods.
     *
     * It tests that the backup is properly exported and then imported.
     * @return void
     * @test
     */
    public function testExportAndImport()
    {
        foreach ($this->getValidExtensions() as $extension) {
            $this->loadFixtures();
            $backup = $this->getAbsolutePath(sprintf('example.%s', $extension));

            //Initial records. 3 articles and 6 comments
            $initial = $this->getAllRecords();
            $this->assertEquals(3, count($initial['Articles']));
            $this->assertEquals(6, count($initial['Comments']));

            //Exports backup and deletes article with ID 2 and comment with ID 4
            $this->assertTrue($this->Driver->export($backup));
            $this->Articles->delete($this->Articles->get(2), ['atomic' => false]);
            $this->Comments->delete($this->Comments->get(4), ['atomic' => false]);

            //Records after delete. 2 articles and 5 comments
            $afterDelete = $this->getAllRecords();
            $this->assertEquals(count($afterDelete['Articles']), count($initial['Articles']) - 1);
            $this->assertEquals(count($afterDelete['Comments']), count($initial['Comments']) - 1);

            //Imports backup. Now initial records are the same of final records
            $this->assertTrue($this->Driver->import($backup));
            $final = $this->getAllRecords();
            $this->assertEquals($initial, $final);

            //Gets the difference (`$diff`) between records after delete
            //  (`$deleted`)and records after import (`$final`)
            $diff = $final;
            foreach ($final as $model => $finalValues) {
                foreach ($finalValues as $finalKey => $finalValue) {
                    foreach ($afterDelete[$model] as $deletedValue) {
                        if ($finalValue == $deletedValue) {
                            unset($diff[$model][$finalKey]);
                        }
                    }
                }
            }
            $this->assertEquals(1, count($diff['Articles']));
            $this->assertEquals(1, count($diff['Comments']));

            //Difference is article with ID 2 and comment with ID 4
            $this->assertEquals(2, collection($diff['Articles'])->extract('id')->first());
            $this->assertEquals(4, collection($diff['Comments'])->extract('id')->first());
        }
    }

    /**
     * Test for `_exportExecutable()` method
     * @return void
     */
    abstract public function testExportExecutable();

    /**
     * Test for `_exportExecutableWithCompression()` method
     * @return void
     * @test
     */
    public function testExportExecutableWithCompression()
    {
        $basicExecutable = $this->invokeMethod($this->Driver, '_exportExecutable');

        //No compression
        $result = $this->invokeMethod($this->Driver, '_exportExecutableWithCompression', ['backup.sql']);
        $expected = sprintf('%s > %s%s', $basicExecutable, escapeshellarg('backup.sql'), REDIRECT_TO_DEV_NULL);
        $this->assertEquals($expected, $result);

        //Gzip and Bzip2 compressions
        foreach (['gzip' => 'backup.sql.gz', 'bzip2' => 'backup.sql.bz2'] as $compression => $filename) {
            $result = $this->invokeMethod($this->Driver, '_exportExecutableWithCompression', [$filename]);
            $expected = sprintf(
                '%s | %s > %s%s',
                $basicExecutable,
                $this->getBinary($compression),
                escapeshellarg($filename),
                REDIRECT_TO_DEV_NULL
            );
            $this->assertEquals($expected, $result);
        }
    }

    /**
     * Test for `export()` method on failure
     * @return void
     * @since 2.6.2
     * @test
     */
    public function testExportOnFailure()
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessageRegExp('/^Failed with exit code `\d`$/');
        //Sets a no existing database
        $config = ['database' => 'noExisting'] + $this->Driver->getConfig();
        $this->setProperty($this->Driver, 'connection', new Connection($config));
        $this->Driver->export($this->getAbsolutePath('example.sql'));
    }

    /**
     * Test for `export()` method. Export is stopped because the
     *  `beforeExport()` method returns `false`
     * @return void
     * @test
     */
    public function testExportStoppedByBeforeExport()
    {
        $backup = $this->getAbsolutePath('example.sql');
        $Driver = $this->getMockForDriver(['beforeExport']);
        $Driver->method('beforeExport')->will($this->returnValue(false));
        $this->assertFalse($Driver->export($backup));
        $this->assertFileNotExists($backup);
    }

    /**
     * Test for `getConfig()` method
     * @return void
     * @test
     */
    public function testGetConfig()
    {
        $this->assertNotEmpty($this->Driver->getConfig());
        $this->assertIsArray($this->Driver->getConfig());
        $this->assertNotEmpty($this->Driver->getConfig('name'));
        $this->assertNull($this->Driver->getConfig('noExistingKey'));
    }

    /**
     * Test for `import()` method
     * @return void
     * @test
     */
    public function testImport()
    {
        $backup = $this->getAbsolutePath('example.sql');
        $this->assertTrue($this->Driver->export($backup));
        $this->assertTrue($this->Driver->import($backup));
        $this->assertEventFired('Backup.beforeImport', $this->Driver->getEventManager());
        $this->assertEventFired('Backup.afterImport', $this->Driver->getEventManager());
    }

    /**
     * Test for `_importExecutable()` method
     * @return void
     */
    abstract public function testImportExecutable();

    /**
     * Test for `_importExecutableWithCompression()` method
     * @return void
     * @test
     */
    public function testImportExecutableWithCompression()
    {
        $basicExecutable = $this->invokeMethod($this->Driver, '_importExecutable');

        //No compression
        $result = $this->invokeMethod($this->Driver, '_importExecutableWithCompression', ['backup.sql']);
        $expected = $basicExecutable . ' < ' . escapeshellarg('backup.sql') . REDIRECT_TO_DEV_NULL;
        $this->assertEquals($expected, $result);

        //Gzip and Bzip2 compressions
        foreach (['gzip' => 'backup.sql.gz', 'bzip2' => 'backup.sql.bz2'] as $compression => $filename) {
            $result = $this->invokeMethod($this->Driver, '_importExecutableWithCompression', [$filename]);
            $expected = sprintf(
                '%s -dc %s | %s%s',
                $this->getBinary($compression),
                escapeshellarg($filename),
                $basicExecutable,
                REDIRECT_TO_DEV_NULL
            );
            $this->assertEquals($expected, $result);
        }
    }

    /**
     * Test for `import()` method on failure
     * @return void
     * @since 2.6.2
     * @test
     */
    public function testImportOnFailure()
    {
        $backup = $this->getAbsolutePath('example.sql');

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessageRegExp('/^Failed with exit code `\d`$/');
        $this->Driver->export($backup);

        //Sets a no existing database
        $config = ['database' => 'noExisting'] + $this->Driver->getConfig();
        $this->setProperty($this->Driver, 'connection', new Connection($config));
        $this->Driver->import($backup);
    }

    /**
     * Test for `import()` method. Import is stopped because the
     *  `beforeImport()` method returns `false`
     * @return void
     * @test
     */
    public function testImportStoppedByBeforeExport()
    {
        $backup = $this->getAbsolutePath('example.sql');
        $Driver = $this->getMockForDriver(['beforeImport']);
        $Driver->method('beforeImport')->will($this->returnValue(false));
        $this->assertTrue($Driver->export($backup));
        $this->assertFalse($Driver->import($backup));
    }
}
