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
use DatabaseBackup\Driver\AbstractDriver;

/**
 * DriverTestCase class.
 *
 * Classes with tests for driver must extend this class.
 */
abstract class DriverTestCase extends TestCase
{
    /**
     * @var \DatabaseBackup\Driver\AbstractDriver
     */
    protected AbstractDriver $Driver;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (empty($this->Driver)) {
            /** @var class-string<\DatabaseBackup\Driver\AbstractDriver> $DriverClass */
            $DriverClass = App::className('DatabaseBackup.' . $this->getDriverName(), 'Driver');
            $this->Driver = new $DriverClass();
        }

        $this->Driver->getEventManager()->on($this->Driver);
    }

    /**
     * @return void
     * @uses \DatabaseBackup\Driver\AbstractDriver::getExportExecutable()
     */
    public function testGetExportExecutable(): void
    {
        $this->assertNotEmpty($this->Driver->getExportExecutable('backup.sql'));

        //Gzip and Bzip2 compressions
        foreach (array_flip(array_filter(DATABASE_BACKUP_EXTENSIONS)) as $compression => $extension) {
            $filename = 'backup.' . $extension;
            $result = $this->Driver->getExportExecutable($filename);
            $expected = sprintf(' | %s > %s', escapeshellarg($this->Driver->getBinary($compression)), escapeshellarg($filename));
            $this->assertStringEndsWith($expected, $result);
        }
    }

    /**
     * @return void
     * @uses \DatabaseBackup\Driver\AbstractDriver::getImportExecutable()
     */
    public function testGetImportExecutable(): void
    {
        $this->assertNotEmpty($this->Driver->getImportExecutable('backup.sql'));

        //Gzip and Bzip2 compressions
        foreach (array_flip(array_filter(DATABASE_BACKUP_EXTENSIONS)) as $compression => $extension) {
            $filename = 'backup.' . $extension;
            $result = $this->Driver->getImportExecutable($filename);
            $expected = sprintf('%s -dc %s | ', escapeshellarg($this->Driver->getBinary($compression)), escapeshellarg($filename));
            $this->assertStringStartsWith($expected, $result);
        }
    }
}
