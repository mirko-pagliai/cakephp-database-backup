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
 * @since       2.0.0
 */

namespace DatabaseBackup\Driver;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Mysql driver to export/import database backups.
 */
class Mysql extends AbstractDriver
{
    /**
     * @since 2.1.0
     * @var string
     */
    private string $auth;

    /**
     * Internal method to get a `Filesystem` instance.
     *
     * @return \Symfony\Component\Filesystem\Filesystem
     */
    protected function getFilesystem(): Filesystem
    {
        return new Filesystem();
    }

    /**
     * Called after export.
     *
     * @return void
     * @since 2.1.0
     */
    public function afterExport(): void
    {
        $this->deleteAuthFile();
    }

    /**
     * Called after import.
     *
     * @return void
     * @since 2.1.0
     */
    public function afterImport(): void
    {
        $this->deleteAuthFile();
    }

    /**
     * Called before export.
     *
     * It stores the authentication data, to be used to export the database, in a temporary file.
     *
     * For security reasons, it's recommended to specify the password in a configuration file and not in the command (a
     * user can execute a `ps aux | grep mysqldump` and see the password).
     * So it creates a temporary file to store the configuration options.
     *
     * @return bool
     * @since 2.1.0
     */
    public function beforeExport(): bool
    {
        return $this->writeAuthFile('[mysqldump]' . PHP_EOL .
            'user={{USER}}' . PHP_EOL .
            'password="{{PASSWORD}}"' . PHP_EOL .
            'host={{HOST}}');
    }

    /**
     * Called before export.
     *
     * It stores the authentication data, to be used to import the database, in a temporary file.
     *
     * For security reasons, it's recommended to specify the password in a configuration file and not in the command (a
     * user can execute a `ps aux | grep mysqldump` and see the password).
     * So it creates a temporary file to store the configuration options.
     *
     * @return bool
     * @since 2.1.0
     */
    public function beforeImport(): bool
    {
        return $this->writeAuthFile('[client]' . PHP_EOL .
            'user={{USER}}' . PHP_EOL .
            'password="{{PASSWORD}}"' . PHP_EOL .
            'host={{HOST}}');
    }

    /**
     * Internal method to get the auth file path.
     *
     * This method returns only the path that will be used and does not verify that the file already exists.
     *
     * @return string
     * @since 2.11.0
     */
    protected function getAuthFilePath(): string
    {
        if (empty($this->auth)) {
            $this->auth = TMP . uniqid('auth');
        }

        return $this->auth;
    }

    /**
     * Internal method to write an auth file.
     *
     * @param string $content Content
     * @return bool
     * @since 2.3.0
     */
    protected function writeAuthFile(string $content): bool
    {
        $content = str_replace(
            [
                '{{USER}}',
                '{{PASSWORD}}',
                '{{HOST}}',
            ],
            [
                (string)$this->getConfig('username'),
                (string)$this->getConfig('password'),
                (string)$this->getConfig('host'),
            ],
            $content
        );

        $Filesystem = $this->getFilesystem();
        $Filesystem->dumpFile($this->getAuthFilePath(), $content);

        return $Filesystem->exists($this->getAuthFilePath());
    }

    /**
     * Deletes the temporary file with the database authentication data.
     *
     * @return void
     * @since 2.1.0
     */
    protected function deleteAuthFile(): void
    {
        $this->getFilesystem()->remove($this->getAuthFilePath());

        unset($this->auth);
    }
}
