<?php
declare(strict_types=1);

namespace DatabaseBackup\Utility;

use Symfony\Component\Process\ExecutableFinder;

/**
 * `ExecutableDiscover` utility is useful for finding the path of the desired executables.
 *
 * It is basically a wrapper around the `ExecutableFinder` provided by Symfony.
 *
 * @since 2.13.4
 */
class ExecutableDiscover
{
    /**
     * Internal method, returns an instance of `ExecutableFinder`.
     *
     * @return \Symfony\Component\Process\ExecutableFinder
     */
    public function getExecutableFinder(): ExecutableFinder
    {
        return new ExecutableFinder();
    }

    /**
     * Finds an executable by name.
     *
     * @param string $name The executable name (without the extension)
     * @return string|null
     */
    public function find(string $name): ?string
    {
        $ExecutableFinder = $this->getExecutableFinder();
        $executable = $ExecutableFinder->find(name: $name);

        //Acts on some aliases
        $aliases = [
            'mariadb' => 'mysql',
            'mariadb-dump' => 'mysqldump',
        ];
        if (in_array($name, $aliases)) {
            return $ExecutableFinder->find(name: array_search($name, $aliases)) ?: $executable;
        }

        return $executable;
    }
}