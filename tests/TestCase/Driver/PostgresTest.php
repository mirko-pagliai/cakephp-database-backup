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
    protected $Driver;

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

        $this->Driver = new Postgres($this->getConnection());
    }

    /**
     * Test for `getDbnameAsString()` method
     * @test
     */
    public function testGetDbnameAsString()
    {
        $result = $this->invokeMethod($this->Driver, 'getDbnameAsString');
        $this->assertEquals('postgresql://postgres@localhost/travis_ci_test', $result);

        //Adds a password to the config
        $config = $this->getProperty($this->Driver, 'config');
        $this->setProperty($this->Driver, 'config', array_merge($config, ['password' => 'mypassword']));

        $result = $this->invokeMethod($this->Driver, 'getDbnameAsString');
        $this->assertEquals('postgresql://postgres:mypassword@localhost/travis_ci_test', $result);
    }

    /**
     * Test for `_exportExecutable()` method
     * @test
     */
    public function testExportExecutable()
    {
        $expected = $this->getBinary('pg_dump') . ' -Fc -b --dbname=postgresql://postgres@localhost/travis_ci_test';
        $result = $this->invokeMethod($this->Driver, '_exportExecutable');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test for `_importExecutable()` method
     * @test
     */
    public function testImportExecutable()
    {
        $expected = $this->getBinary('pg_restore') . ' -c -e --dbname=postgresql://postgres@localhost/travis_ci_test';
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
        //Sets a no existing database
        $config = $this->getProperty($this->Driver, 'config');
        $this->setProperty($this->Driver, 'config', array_merge($config, ['database' => 'noExisting']));

        $this->Driver->export($this->getAbsolutePath('example.sql'));
    }

    /**
     * Test for `import()` method on failure
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Failed with exit code `1`
     * @test
     */
    public function testImportOnFailure()
    {
        $backup = $this->getAbsolutePath('example.sql');

        $this->Driver->export($backup);

        //Sets a no existing database
        $config = $this->getProperty($this->Driver, 'config');
        $this->setProperty($this->Driver, 'config', array_merge($config, ['database' => 'noExisting']));

        $this->Driver->import($backup);
    }
}
