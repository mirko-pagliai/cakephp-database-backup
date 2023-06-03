<?php
/** @noinspection PhpUnhandledExceptionInspection */
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

use DatabaseBackup\Driver\Driver;
use Tools\TestSuite\ReflectionTrait;

/**
 * DriverTestCase class.
 *
 * Classes with tests for driver must extend this class.
 */
abstract class DriverTestCase extends TestCase
{
    use ReflectionTrait;

    /**
     * @var \DatabaseBackup\Driver\Driver
     */
    protected Driver $Driver;


    /**
     * Called before every test method
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        /** @var \Cake\Database\Connection $connection */
        $connection = $this->getConnection('test');

        if (empty($this->DriverClass) || empty($this->Driver)) {
            /** @var class-string<\DatabaseBackup\Driver\Driver> $DriverClass */
            $DriverClass = 'DatabaseBackup\\Driver\\' . get_class_short_name($connection->config()['driver']);
            $this->Driver = new $DriverClass($connection);
        }
    }

    /**
     * @return void
     * @throws \ReflectionException|\ErrorException
     * @uses \DatabaseBackup\Driver\Driver::_getExportExecutable()
     */
    public function testGetExportExecutable(): void
    {
        $this->assertNotEmpty($this->invokeMethod($this->Driver, '_getExportExecutable', ['backup.sql']));

        //Gzip and Bzip2 compressions
        foreach (['gzip' => 'backup.sql.gz', 'bzip2' => 'backup.sql.bz2'] as $compression => $filename) {
            $result = $this->invokeMethod($this->Driver, '_getExportExecutable', [$filename]);
            $expected = sprintf(' | %s > %s', escapeshellarg($this->Driver->getBinary($compression)), escapeshellarg($filename));
            $this->assertStringEndsWith($expected, $result);
        }
    }

    /**
     * @return void
     * @throws \ReflectionException|\ErrorException
     * @uses \DatabaseBackup\Driver\Driver::_getImportExecutable()
     */
    public function testGetImportExecutable(): void
    {
        $this->assertNotEmpty($this->invokeMethod($this->Driver, '_getImportExecutable', ['backup.sql']));

        //Gzip and Bzip2 compressions
        foreach (['gzip' => 'backup.sql.gz', 'bzip2' => 'backup.sql.bz2'] as $compression => $filename) {
            $result = $this->invokeMethod($this->Driver, '_getImportExecutable', [$filename]);
            $expected = sprintf('%s -dc %s | ', escapeshellarg($this->Driver->getBinary($compression)), escapeshellarg($filename));
            $this->assertStringStartsWith($expected, $result);
        }
    }
}
