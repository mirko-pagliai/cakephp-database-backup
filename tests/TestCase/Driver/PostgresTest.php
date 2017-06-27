<?php
/**
 * This file is part of cakephp-database-backup.
 *
 * cakephp-database-backup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * cakephp-database-backup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with cakephp-database-backup.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright   Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license     http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link        http://git.novatlantis.it Nova Atlantis Ltd
 */
namespace DatabaseBackup\Test\TestCase\Driver;

use Cake\Core\Configure;
use DatabaseBackup\BackupTrait;
use DatabaseBackup\Driver\Postgres;
use DatabaseBackup\TestSuite\DriverTestCase;
use Reflection\ReflectionTrait;

/**
 * PostgresTest class
 */
class PostgresTest extends DriverTestCase
{
    use BackupTrait;
    use ReflectionTrait;

    /**
     * @var \DatabaseBackup\Driver\Postgres
     */
    protected $Postgres;

    /**
     * @var bool
     */
    public $autoFixtures = false;

    /**
     * Fixtures
     * @var array
     */
    public $fixtures = [
        'plugin.database_backup.Postgres/Articles',
        'plugin.database_backup.Postgres/Comments',
    ];

    /**
     * Setup the test case, backup the static object values so they can be
     * restored. Specifically backs up the contents of Configure and paths in
     *  App if they have not already been backed up
     * @return void
     */
    public function setUp()
    {
        Configure::write(DATABASE_BACKUP . '.connection', 'test_postgres');

        parent::setUp();

        $this->Postgres = new Postgres($this->getConnection());
    }

    /**
     * Teardown any static object changes and restore them
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        Configure::write(DATABASE_BACKUP . '.connection', 'test');

        unset($this->Sqlite);
    }

    /**
     * Test for `getCompression()` method
     * @test
     */
    public function testGetCompression()
    {
        $compressions = [
            'backup.sql' => false,
            'backup.sql.bz2' => 'bzip2',
            'backup.sql.gz' => 'gzip',
            'text.txt' => null,
        ];

        foreach ($compressions as $filename => $expectedCompression) {
            $this->assertEquals($expectedCompression, $this->Postgres->getCompression($filename));
        }
    }

    /**
     * Test for `getDefaultExtension()` method
     * @test
     */
    public function testGetDefaultExtension()
    {
        $this->assertEquals('sql', $this->Postgres->getDefaultExtension());
    }

    /**
     * Test for `getExportExecutable()` method
     * @test
     */
    public function testGetExportExecutable()
    {
        $method = 'getExportExecutable';
        $pgDump = $this->getBinary('pg_dump');
        $dbnameAsString = 'postgresql://postgres@localhost/travis_ci_test';

        $expected = $pgDump . ' -Fc -b --dbname=' . $dbnameAsString . ' | ' . $this->getBinary('bzip2') . ' > backup.sql.bz2 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Postgres, $method, ['backup.sql.bz2']));

        $expected = $pgDump . ' -Fc -b --dbname=' . $dbnameAsString . ' | ' . $this->getBinary('gzip') . ' > backup.sql.gz 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Postgres, $method, ['backup.sql.gz']));

        $expected = $pgDump . ' -Fc -b --dbname=postgresql://postgres@localhost/travis_ci_test > backup.sql 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Postgres, $method, ['backup.sql']));
    }

    /**
     * Test for `getImportExecutable()` method
     * @test
     */
    public function testGetImportExecutable()
    {
        $method = 'getImportExecutable';
        $pgRestore = $this->getBinary('pg_restore');
        $dbnameAsString = 'postgresql://postgres@localhost/travis_ci_test';

        $expected = $this->getBinary('bzip2') . ' -dc backup.sql.bz2 | ' . $pgRestore . ' -c -e --dbname=' . $dbnameAsString . ' 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Postgres, $method, ['backup.sql.bz2']));

        $expected = $this->getBinary('gzip') . ' -dc backup.sql.gz | ' . $pgRestore . ' -c -e --dbname=' . $dbnameAsString . ' 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Postgres, $method, ['backup.sql.gz']));

        $expected = $pgRestore . ' -c -e --dbname=' . $dbnameAsString . ' < backup.sql 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Postgres, $method, ['backup.sql']));
    }

    /**
     * Test for `export()` method
     * @test
     */
    public function testExport()
    {
        $backup = $this->getAbsolutePath('example.sql');

        $this->assertTrue($this->Postgres->export($backup));
        $this->assertFileExists($backup);
    }

    /**
     * Test for `export()` method on failure
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage pg_dump failed with exit code `1`
     * @test
     */
    public function testExportOnFailure()
    {
        //Sets a no existing database
        $config = $this->getProperty($this->Postgres, 'config');
        $this->setProperty($this->Postgres, 'config', array_merge($config, ['database' => 'noExisting']));

        $this->Postgres->export($this->getAbsolutePath('example.sql'));
    }

    /**
     * Test for `import()` method
     * @test
     */
    public function testImport()
    {
        $backup = $this->getAbsolutePath('example.sql');

        $this->Postgres->export($backup);

        $this->assertTrue($this->Postgres->import($backup));
    }

    /**
     * Test for `import()` method on failure
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage pg_restore failed with exit code `1`
     * @test
     */
    public function testImportOnFailure()
    {
        $backup = $this->getAbsolutePath('example.sql');

        $this->Postgres->export($backup);

        //Sets a no existing database
        $config = $this->getProperty($this->Postgres, 'config');
        $this->setProperty($this->Postgres, 'config', array_merge($config, ['database' => 'noExisting']));

        $this->Postgres->import($backup);
    }

    /**
     * Test for `export()` and `import()` methods.
     *
     * It tests that the backup is properly exported and then imported.
     * @test
     */
    public function testExportAndImport()
    {
        $this->loadFixtures('Postgres\Articles', 'Postgres\Comments');

        $this->_testExportAndImport($this->Postgres);
    }
}
