<?php
/** @noinspection PhpDocMissingThrowsInspection, PhpUnhandledExceptionInspection */
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

use Cake\Core\App;
use Cake\Datasource\ConnectionManager;
use DatabaseBackup\Compression;
use DatabaseBackup\Executor\AbstractExecutor;

/**
 * DriverTestCase class.
 *
 * Classes with tests for driver must extend this class.
 */
abstract class DriverTestCase extends TestCase
{
    /**
     * @var \DatabaseBackup\Executor\AbstractExecutor
     */
    protected AbstractExecutor $Executor;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $Connection = ConnectionManager::get('test');

        //For example `$driverName` is `Mysql`
        $driverName = substr(strrchr($Connection->getDriver()::class, '\\') ?: '', 1);
        /** @var class-string<\DatabaseBackup\Executor\AbstractExecutor> $executorClassName */
        $executorClassName = App::classname('DatabaseBackup.' . $driverName . 'Executor', 'Executor');

        $this->Executor = new $executorClassName($Connection);
    }

    /**
     * @return void
     * @uses \DatabaseBackup\Executor\AbstractExecutor::getExportExecutable()
     */
    public function testGetExportExecutable(): void
    {
        $this->assertNotEmpty($this->Executor->getExportExecutable('backup.sql'));

        $cases = array_filter(
            array: Compression::cases(),
            callback: fn (Compression $Compression): bool => $Compression !== Compression::None
        );

        //Gzip and Bzip2 compressions
        foreach ($cases as $Compression) {
            $filename = 'backup.' . $Compression->value;
            $result = $this->Executor->getExportExecutable($filename);
            $expected = sprintf(
                ' | %s > %s',
                escapeshellarg($this->Executor->getBinary($Compression)),
                escapeshellarg($filename)
            );
            $this->assertStringEndsWith($expected, $result);
        }
    }

    /**
     * @return void
     * @uses \DatabaseBackup\Executor\AbstractExecutor::getImportExecutable()
     */
    public function testGetImportExecutable(): void
    {
        $this->assertNotEmpty($this->Executor->getImportExecutable('backup.sql'));

        $cases = array_filter(
            array: Compression::cases(),
            callback: fn (Compression $Compression): bool => $Compression !== Compression::None
        );

        //Gzip and Bzip2 compressions
        foreach ($cases as $Compression) {
            $filename = 'backup.' . $Compression->value;
            $result = $this->Executor->getImportExecutable($filename);
            $expected = sprintf(
                '%s -dc %s | ',
                escapeshellarg($this->Executor->getBinary($Compression)),
                escapeshellarg($filename)
            );
            $this->assertStringStartsWith($expected, $result);
        }
    }
}
