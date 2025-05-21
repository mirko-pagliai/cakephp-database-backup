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

namespace DatabaseBackup\Executor;

use Cake\Event\EventInterface;
use DatabaseBackup\OperationType;
use Override;
use Symfony\Component\Filesystem\Filesystem;

/**
 * MysqlExecutor to export/import database backups.
 */
class MysqlExecutor extends Executor
{
    protected(set) string $authFile {
        get {
            if (empty($this->authFile)) {
                $this->authFile = TMP . uniqid('auth');
            }

            return $this->authFile;
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getBinaryName(): string
    {
        return $this->OperationType == OperationType::Export ? 'mariadb-dump' : 'mariadb';
    }

    /**
     * Called after export.
     *
     * @return void
     * @since 2.1.0
     */
    #[Override]
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
    #[Override]
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
     * @param \Cake\Event\EventInterface<object> $Event
     * @return void
     * @since 2.1.0
     */
    #[Override]
    public function beforeExport(EventInterface $Event): void
    {
        $result = $this->writeAuthFile('[mysqldump]' . PHP_EOL .
            'user={{USER}}' . PHP_EOL .
            'password="{{PASSWORD}}"' . PHP_EOL .
            'host={{HOST}}');

        $Event->setResult($result);
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
     * @param \Cake\Event\EventInterface<object> $Event
     * @return void
     * @since 2.1.0
     */
    #[Override]
    public function beforeImport(EventInterface $Event): void
    {
        $result = $this->writeAuthFile('[client]' . PHP_EOL .
            'user={{USER}}' . PHP_EOL .
            'password="{{PASSWORD}}"' . PHP_EOL .
            'host={{HOST}}');

        $Event->setResult($result);
    }

    /**
     * Internal method to write the auth file.
     *
     * @param string $content Content
     * @return bool
     * @since 2.3.0
     */
    public function writeAuthFile(string $content): bool
    {
        $config = $this->Connection->config();

        $content = str_replace(
            search: ['{{USER}}', '{{PASSWORD}}', '{{HOST}}'],
            replace: [$config['username'], $config['password'], $config['host']],
            subject: $content
        );

        $Filesystem = new Filesystem();
        $Filesystem->dumpFile($this->authFile, $content);

        return $Filesystem->exists($this->authFile);
    }

    /**
     * Deletes the temporary file with the database authentication data.
     *
     * @return void
     * @since 2.1.0
     */
    public function deleteAuthFile(): void
    {
        new Filesystem()->remove($this->authFile);

        //Resets the property.
        $this->authFile = '';
    }
}
