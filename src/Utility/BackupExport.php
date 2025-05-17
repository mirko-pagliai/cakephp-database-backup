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

/**
 * Utility to export databases.
 */
class BackupExport extends Utility
{
    /**
     * Replaces filename patterns.
     *
     * @param string $filename
     * @return string
     */
    public function replaceFilenamePatterns(string $filename): string
    {
        return str_replace(
            search: ['{$DATABASE}', '{$DATETIME}', '{$HOSTNAME}', '{$TIMESTAMP}'],
            replace: [
                $this->Connection->config()['database'],
                date('YmdHis'),
                str_replace(['127.0.0.1', '::1'], 'localhost', $this->Connection->config()['host'] ?? 'localhost'),
                (string)time(),
            ],
            subject: $filename,
        );
    }
}
