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
     * Called before every test method
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        if (!$this->Driver instanceof Postgres) {
            $this->markTestIncomplete();
        }
    }

    /**
     * Test for `getDbnameAsString()` method
     * @test
     */
    public function testGetDbnameAsString(): void
    {
        $password = $this->Driver->getConfig('password');
        $expected = 'postgresql://postgres' . ($password ? ':' . $password : null) . '@' . $this->Driver->getConfig('host') . '/' . $this->Driver->getConfig('database');
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, 'getDbnameAsString'));

        //Adds a password to the config
        $expected = 'postgresql://postgres:mypassword@' . $this->Driver->getConfig('host') . '/' . $this->Driver->getConfig('database');
        $config = ['password' => 'mypassword'] + $this->Driver->getConfig();
        $this->setProperty($this->Driver, 'connection', new Connection($config));
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, 'getDbnameAsString'));
    }

    /**
     * Test for `_exportExecutable()` method
     * @test
     */
    public function testExportExecutable(): void
    {
        //Sets a password
        $connection = $this->getProperty($this->Driver, 'connection');
        $config = $this->getProperty($connection, '_config') + ['password' => 'mysecret'];
        $this->setProperty($connection, '_config', $config);
        $this->setProperty($this->Driver, 'connection', $connection);

        $expected = $this->Driver->getBinary('pg_dump') . ' --format=c -b --dbname=' . escapeshellarg('postgresql://postgres:mysecret@' . $this->Driver->getConfig('host') . '/' . $this->Driver->getConfig('database'));
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, '_exportExecutable'));
    }

    /**
     * Test for `_importExecutable()` method
     * @test
     */
    public function testImportExecutable(): void
    {
        //Sets a password
        $connection = $this->getProperty($this->Driver, 'connection');
        $config = $this->getProperty($connection, '_config') + ['password' => 'mysecret'];
        $this->setProperty($connection, '_config', $config);
        $this->setProperty($this->Driver, 'connection', $connection);

        $expected = $this->Driver->getBinary('pg_restore') . ' --format=c -c -e --dbname=' . escapeshellarg('postgresql://postgres:mysecret@' . $this->Driver->getConfig('host') . '/' . $this->Driver->getConfig('database'));
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, '_importExecutable'));
    }
}
