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

namespace DatabaseBackup\Utility;

use DatabaseBackup\Compression;
use DatabaseBackup\OperationType;
use Symfony\Component\Filesystem\Exception\IOException;
use function Cake\I18n\__d;

/**
 * Utility to import databases.
 */
class BackupImport extends Utility
{
    public string $filename {
        set(string $filename) {
            $filename = $this->makeAbsolutePath(path: $filename);
            if (!is_readable($filename)) {
                throw new IOException(
                    __d('database_backup', 'File or directory `{0}` is not readable', $filename)
                );
            }

            //This is only useful to possibly throw a `ValueError`
            Compression::fromFilename($filename);

            $this->filename = $filename;
        }
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(OperationType: OperationType::Import);
    }
}
