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
     * Array of aliases.
     *
     * When asked to find one of these executables, it will first try returning the alias executable.
     *
     * For example, if asked to find `mysql`, it will try to find `mariadb` first and then `mysql`.
     */
    protected const ALIASES = [
        'mysql' => 'mariadb',
        'mysql-dump' => 'mariadb-dump',
    ];

    /**
     * Internal method. Returns an instance of `ExecutableFinder`.
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
     * @param string $name The executable name
     * @return string|null
     */
    public function find(string $name): ?string
    {
        $ExecutableFinder = $this->getExecutableFinder();
        $executable = $ExecutableFinder->find(name: $name);

        if (isset(self::ALIASES[$name])) {
            return $ExecutableFinder->find(name: self::ALIASES[$name], default: $executable);
        }

        return $executable;
    }
}
