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
 * @see         https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility
 */
namespace DatabaseBackup\Utility;

use DatabaseBackup\BackupTrait;
use DatabaseBackup\Driver\Driver;

/**
 * AbstractBackupUtility.
 *
 * Provides the code common to the `BackupExport` and `BackupImport` classes.
 */
abstract class AbstractBackupUtility
{
    use BackupTrait;

    /**
     * Filename where to export/import the database
     * @var string
     */
    protected string $filename;

    /**
     * Driver containing all methods to export/import database backups according to the connection
     * @var \DatabaseBackup\Driver\Driver
     */
    public Driver $Driver;

    /**
     * Sets the filename
     * @param string $filename Filename. It can be an absolute path
     * @return $this
     */
    abstract function filename(string $filename);
}
