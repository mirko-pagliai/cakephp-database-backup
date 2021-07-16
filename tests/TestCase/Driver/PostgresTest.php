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
     * @var string
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
    public function testGetDbnameAsString(): void
    {
        $password = $this->Driver->getConfig('password');
        $expected = 'postgresql://postgres' . ($password ? ':' . $password : null) . '@localhost/travis_ci_test';
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, 'getDbnameAsString'));

        //Adds a password to the config
        $expected = 'postgresql://postgres:mypassword@localhost/travis_ci_test';
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
        $password = $this->Driver->getConfig('password');
        $expected = sprintf(
            '%s --format=c -b --dbname=postgresql://postgres%s@localhost/travis_ci_test',
            $this->Driver->getBinary('pg_dump'),
            $password ? ':' . $password : ''
        );
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, '_exportExecutable'));
    }

    /**
     * Test for `_importExecutable()` method
     * @test
     */
    public function testImportExecutable(): void
    {
        $password = $this->Driver->getConfig('password');
        $expected = sprintf(
            '%s --format=c -c -e --dbname=postgresql://postgres%s@localhost/travis_ci_test',
            $this->Driver->getBinary('pg_restore'),
            $password ? ':' . $password : ''
        );
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, '_importExecutable'));
    }
}
