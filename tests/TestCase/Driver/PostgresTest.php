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

        $this->Driver = new Postgres($this->getConnection());

        parent::setUp();
    }

    /**
     * Test for `getDbnameAsString()` method
     * @test
     */
    public function testGetDbnameAsString()
    {
        $config = $this->getProperty($this->Driver, 'config');
        $password = null;

        if (!empty($config['password'])) {
            $password = ':' . $config['password'];
        }

        $expected = 'postgresql://postgres' . $password . '@localhost/travis_ci_test';

        $this->assertEquals($expected, $this->invokeMethod($this->Driver, 'getDbnameAsString'));

        //Adds a password to the config
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
        $config = $this->getProperty($this->Driver, 'config');
        $password = null;

        if (!empty($config['password'])) {
            $password = ':' . $config['password'];
        }

        $expected = $this->getBinary('pg_dump') . ' -Fc -b --dbname=postgresql://postgres' . $password . '@localhost/travis_ci_test';
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, '_exportExecutable'));
    }

    /**
     * Test for `_importExecutable()` method
     * @test
     */
    public function testImportExecutable()
    {
        $config = $this->getProperty($this->Driver, 'config');
        $password = null;

        if (!empty($config['password'])) {
            $password = ':' . $config['password'];
        }

        $expected = $this->getBinary('pg_restore') . ' -c -e --dbname=postgresql://postgres' . $password . '@localhost/travis_ci_test';
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, '_importExecutable'));
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
