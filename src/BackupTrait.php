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

namespace DatabaseBackup;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * A trait that provides some methods used by all other classes.
 */
trait BackupTrait
{
    /**
     * Returns the absolute path for a backup file.
     *
     * @param string $path Relative or absolute path
     * @return string
     */
    public static function getAbsolutePath(string $path): string
    {
        $Filesystem = new Filesystem();
        if ($Filesystem->isAbsolutePath($path)) {
            return $path;
        }

        if (is_readable(Path::makeAbsolute($path, ROOT))) {
            return Path::makeAbsolute($path, ROOT);
        }

        return Path::makeAbsolute($path, Configure::readOrFail('DatabaseBackup.target'));
    }

    /**
     * Returns the compression type for a backup file.
     *
     * @param string $path File path
     * @return string|null Compression type or `null`
     */
    public static function getCompression(string $path): ?string
    {
        $Compression = Compression::tryFromFilename($path);

        return $Compression && $Compression !== Compression::None ? lcfirst($Compression->name) : null;
    }

    /**
     * Gets the `Connection` instance.
     *
     * You can pass the name of the connection. By default, the connection set in the configuration will be used.
     *
     * @param string|null $name Connection name
     * @return \Cake\Datasource\ConnectionInterface
     */
    public static function getConnection(?string $name = null): ConnectionInterface
    {
        return ConnectionManager::get($name ?: Configure::readOrFail('DatabaseBackup.connection'));
    }

    /**
     * Gets the driver name, according to the connection.
     *
     * @return string Driver name
     * @since 2.9.2
     */
    public function getDriverName(): string
    {
        $className = get_class($this->getConnection()->getDriver());

        return substr($className, strrpos($className, '\\') + 1);
    }

    /**
     * Takes and gets the extension of a backup file.
     *
     * @param string $path File path
     * @return string|null Extension or `null` for invalid extensions
     */
    public static function getExtension(string $path): ?string
    {
        return Compression::tryFromFilename($path)?->value;
    }

    /**
     * Returns all valid compressions available.
     *
     * @return array<string, string> An array with extensions as keys and compressions as values
     * @since 2.4.0
     */
    public static function getValidCompressions(): array
    {
        return array_filter(DATABASE_BACKUP_EXTENSIONS);
    }
}
