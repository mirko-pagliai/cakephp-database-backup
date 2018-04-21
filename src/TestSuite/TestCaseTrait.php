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
 * @since       2.2.0
 */
namespace DatabaseBackup\TestSuite;

use Cake\Core\Configure;

/**
 * TestCaseTrait class
 */
trait TestCaseTrait
{
    /**
     * Internal method to create a backup file
     * @return string
     */
    protected function createBackup()
    {
        return $this->BackupExport->filename('backup.sql')->export();
    }

    /**
     * Internal method to creates some backup files
     * @param bool $sleep If `true`, waits a second for each backup
     * @return array
     */
    protected function createSomeBackups($sleep = false)
    {
        $files[] = $this->BackupExport->filename('backup.sql')->export();

        if ($sleep) {
            sleep(1);
        }

        $files[] = $this->BackupExport->filename('backup.sql.bz2')->export();

        if ($sleep) {
            sleep(1);
        }

        $files[] = $this->BackupExport->filename('backup.sql.gz')->export();

        return $files;
    }

    /**
     * Deletes all backups
     * @return void
     */
    public function deleteAllBackups()
    {
        foreach (glob(Configure::read(DATABASE_BACKUP . '.target') . DS . '*') as $file) {
            safe_unlink($file);
        }
    }
}
