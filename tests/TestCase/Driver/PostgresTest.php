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

use Cake\Database\Connection;
use DatabaseBackup\Driver\Postgres;
use DatabaseBackup\TestSuite\DriverTestCase;

/**
 * PostgresTest class
 */
class PostgresTest extends DriverTestCase
{
    /**
     * @var \DatabaseBackup\Driver\Postgres
     */
    protected $DriverClass = Postgres::class;

    /**
     * Name of the database connection
     * @var string
     */
    protected $connection = 'test_postgres';

    /**
     * Fixtures
     * @var array
     */
    public $fixtures = [
        'plugin.DatabaseBackup.Postgres/Articles',
        'plugin.DatabaseBackup.Postgres/Comments',
    ];

    /**
     * Test for `getDbnameAsString()` method
     * @test
     */
    public function testGetDbnameAsString()
    {
        $password = $this->Driver->getConfig('password');

        if ($password) {
            $password = ':' . $password;
        }

        $expected = 'postgresql://postgres' . $password . '@localhost/travis_ci_test';
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, 'getDbnameAsString'));

        //Adds a password to the config
        $config = array_merge($this->Driver->getConfig(), ['password' => 'mypassword']);
        $this->setProperty($this->Driver, 'connection', new Connection($config));

        $expected = 'postgresql://postgres:mypassword@localhost/travis_ci_test';
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, 'getDbnameAsString'));
    }

    /**
     * Test for `_exportExecutable()` method
     * @test
     */
    public function testExportExecutable()
    {
        $password = $this->Driver->getConfig('password');

        if ($password) {
            $password = ':' . $password;
        }

        $expected = sprintf(
            '%s --format=c -b --dbname=postgresql://postgres%s@localhost/travis_ci_test',
            $this->getBinary('pg_dump'),
            $password
        );
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, '_exportExecutable'));
    }

    /**
     * Test for `_importExecutable()` method
     * @test
     */
    public function testImportExecutable()
    {
        $password = $this->Driver->getConfig('password');

        if ($password) {
            $password = ':' . $password;
        }

        $expected = sprintf(
            '%s --format=c -c -e --dbname=postgresql://postgres%s@localhost/travis_ci_test',
            $this->getBinary('pg_restore'),
            $password
        );
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, '_importExecutable'));
    }

    /**
     * Test for `export()` method on failure
     * @expectedException RuntimeException
     * @expectedExceptionMessage Failed with exit code `1`
     * @test
     */
    public function testExportOnFailure()
    {
        //Sets a no existing database
        $config = array_merge($this->Driver->getConfig(), ['database' => 'noExisting']);
        $this->setProperty($this->Driver, 'connection', new Connection($config));

        $this->Driver->export($this->getAbsolutePath('example.sql'));
    }

    /**
     * Test for `import()` method on failure
     * @expectedException RuntimeException
     * @expectedExceptionMessage Failed with exit code `1`
     * @test
     */
    public function testImportOnFailure()
    {
        $backup = $this->getAbsolutePath('example.sql');

        $this->Driver->export($backup);

        //Sets a no existing database
        $config = array_merge($this->Driver->getConfig(), ['database' => 'noExisting']);
        $this->setProperty($this->Driver, 'connection', new Connection($config));

        $this->Driver->import($backup);
    }
}
