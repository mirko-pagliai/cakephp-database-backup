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
 */
namespace DatabaseBackup;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Filesystem\Folder;
use InvalidArgumentException;

/**
 * A trait that provides some methods used by all other classes
 */
trait BackupTrait
{
    /**
     * Valid extensions (as keys) and compressions (as values)
     * @since 2.4.0
     * @var array
     */
    private static $validExtensions = ['sql.bz2' => 'bzip2', 'sql.gz' => 'gzip', 'sql' => false];

    /**
     * Returns an absolute path
     * @param string $path Relative or absolute path
     * @return string
     * @uses getTarget()
     */
    public function getAbsolutePath($path)
    {
        return Folder::isAbsolute($path) ? $path : $this->getTarget() . DS . $path;
    }

    /**
     * Gets a binary path
     * @param string $name Binary name
     * @return string
     * @since 2.0.0
     */
    public function getBinary($name)
    {
        return Configure::readOrFail(DATABASE_BACKUP . '.binaries.' . $name);
    }

    /**
     * Returns the compression type from a filename
     * @param string $filename Filename
     * @return string|bool Compression type as string or `false`
     * @uses getExtension()
     * @uses getValidCompressions()
     */
    public function getCompression($filename)
    {
        //Gets the extension from the filename
        $extension = $this->getExtension($filename);

        if (!array_key_exists($extension, $this->getValidCompressions())) {
            return false;
        }

        return $this->getValidCompressions()[$extension];
    }

    /**
     * Gets the connection array
     * @param string|null $name Connection name
     * @return \Cake\Datasource\ConnectionInterface A connection object
     */
    public function getConnection($name = null)
    {
        return ConnectionManager::get($name ?: Configure::readOrFail(DATABASE_BACKUP . '.connection'));
    }

    /**
     * Gets the driver containing all methods to export/import database backups
     *  according to the database engine
     * @param \Cake\Datasource\ConnectionInterface|null $connection A connection object
     * @return object A driver instance
     * @since 2.0.0
     * @throws InvalidArgumentException
     * @uses getConnection()
     */
    public function getDriver(ConnectionInterface $connection = null)
    {
        $connection = $connection ?: $this->getConnection();
        $className = get_class_short_name($connection->getDriver());
        $driver = App::classname(sprintf('%s.%s', DATABASE_BACKUP, $className), 'Driver');

        if (!$driver) {
            throw new InvalidArgumentException(__d('database_backup', 'The `{0}` driver does not exist', $className));
        }

        return new $driver($connection);
    }

    /**
     * Returns the extension from a filename
     * @param string $filename Filename
     * @return string|null Extension or `null` if the extension is not found or
     *  if is an invalid extension
     * @uses getValidExtensions()
     */
    public function getExtension($filename)
    {
        $extension = get_extension($filename);

        return in_array($extension, $this->getValidExtensions()) ? $extension : null;
    }

    /**
     * Returns the target path
     * @return string
     */
    public function getTarget()
    {
        return Configure::read(DATABASE_BACKUP . '.target');
    }

    /**
     * Returns all valid compressions
     * @return array
     * @since 2.4.0
     * @uses $$validExtensions
     */
    public function getValidCompressions()
    {
        return array_filter(self::$validExtensions);
    }

    /**
     * Returns all valid extensions
     * @return array
     * @since 2.4.0
     * @uses $validExtensions
     */
    public function getValidExtensions()
    {
        return array_keys(self::$validExtensions);
    }
}
