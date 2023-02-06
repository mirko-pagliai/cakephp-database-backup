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

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use DatabaseBackup\Driver\Driver;
use Tools\Exceptionist;
use Tools\Filesystem;

/**
 * A trait that provides some methods used by all other classes
 */
trait BackupTrait
{
    /**
     * Returns the absolute path for a backup file
     * @param string $path Relative or absolute path
     * @return string
     */
    public static function getAbsolutePath(string $path): string
    {
        return Filesystem::instance()->makePathAbsolute($path, Configure::readOrFail('DatabaseBackup.target'));
    }

    /**
     * Returns the compression type for a backup file
     * @param string $path File path
     * @return string|null Compression type or `null`
     */
    public static function getCompression(string $path): ?string
    {
        $extension = self::getExtension($path);

        return self::getValidCompressions()[$extension] ?? null;
    }

    /**
     * Gets the `Connection` instance
     * @param string|null $name Connection name
     * @return \Cake\Datasource\ConnectionInterface
     */
    public static function getConnection(?string $name = null): ConnectionInterface
    {
        return ConnectionManager::get($name ?: Configure::readOrFail('DatabaseBackup.connection'));
    }

    /**
     * Gets the `Driver` instance containing all methods to export/import database backups, according to the connection
     * @param \Cake\Datasource\ConnectionInterface|null $connection A `Connection` object
     * @return \DatabaseBackup\Driver\Driver A `Driver` instance
     * @throws \ErrorException|\ReflectionException
     * @since 2.0.0
     */
    public static function getDriver(?ConnectionInterface $connection = null): Driver
    {
        $connection = $connection ?: self::getConnection();
        $name = self::getDriverName($connection);
        /** @var class-string<\DatabaseBackup\Driver\Driver> $Driver */
        $Driver = App::classname('DatabaseBackup.' . $name, 'Driver');
        Exceptionist::isTrue($Driver, __d('database_backup', 'The `{0}` driver does not exist', $name));

        return new $Driver($connection);
    }

    /**
     * Gets the driver name, according to the connection
     * @param \Cake\Datasource\ConnectionInterface|null $connection A `Connection` object
     * @return string Driver name
     * @throws \ReflectionException
     * @since 2.9.2
     */
    public static function getDriverName(?ConnectionInterface $connection = null): string
    {
        $connection = $connection ?: self::getConnection();

        return get_class_short_name($connection->getDriver());
    }

    /**
     * Returns the extension for a backup file
     * @param string $path File path
     * @return string|null Extension or `null` for invalid extensions
     */
    public static function getExtension(string $path): ?string
    {
        $extension = Filesystem::instance()->getExtension($path);

        return in_array($extension, array_keys(DATABASE_BACKUP_EXTENSIONS)) ? $extension : null;
    }

    /**
     * Returns all valid compressions
     * @return array<string, string>
     * @since 2.4.0
     */
    public static function getValidCompressions(): array
    {
        return array_filter(DATABASE_BACKUP_EXTENSIONS);
    }
}
