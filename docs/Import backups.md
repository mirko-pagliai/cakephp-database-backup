# Import backups

> [!WARNING]  
> Please note: These backup import commands are for illustrative purposes only.
> 
> You shouldn't normally use them in a production environment; in fact, they shouldn't be present.  
> This means that if you're testing them in a development environment, you should add them to your `.gitignore`; or [you could use an environment variable](https://book.cakephp.org/5/en/development/configuration.html#environment-variables) and make the command exit if it's not the development environment.

## Import the latest backup file

This example is the simplest case: the command imports the latest available backup file.

`$target` is the directory where the backup files were previously exported.  
`$databaseName` is the name of the default connection's database.

The `Finder` instance will search for all available backup files and then sort them (using the `sort()` method) by name (here it won't be necessary to sort them by file date, since the file names reflect the creation date).

Finally, it proceeds with the import process.

```php
<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use DatabaseBackup\Utility\BackupImport;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * ImportBackupCommand.
 */
class ImportBackupCommand extends Command
{
    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        /** @var string $target */
        $target = Configure::readOrFail('DatabaseBackup.target');

        /** @var string $databaseName */
        $databaseName = ConnectionManager::get('default')->config()['database'];

        $Finder = new Finder()
            ->files()
            ->name('/^backup_' . $databaseName . '_\d{14}\.sql(\.bz2|\.gz)?$/')
            ->in($target)
            ->sort(fn(SplFileInfo $a, SplFileInfo $b): int => strcmp($b->getRealPath(), $a->getRealPath()));

        if (!$Finder->hasResults()) {
            $io->abort('No backup files found in `' . $target . '`');
        }

        /**
         * It only gets the first backup file.
         *
         * @var \Symfony\Component\Finder\SplFileInfo $File
         */
        $File = array_values(iterator_to_array($Finder))[0];

        /**
         * Imports the backup file.
         */
        $BackupImport = new BackupImport()->filename($File->getRealPath());
        $result = $BackupImport->import();
        if (!$result) {
            $io->abort('Backup file `' . $File->getRealPath() . '` could not be imported');
        }

        $io->success('Backup file `' . $result . '` imported successfully');

        return static::CODE_SUCCESS;
    }
}
```

## Import the latest backup file for multiple connections (databases)

Let's now move on to a more complex case.

Real-world example: my application uses two different connections (and therefore two different databases), `default` and `logs` (the latter containing tables and log data).  
Using cron jobs, the databases are exported programmatically according to different criteria: for example, the `default` database is exported several times a day, while the `log` database is exported only once a day or a few times a week.

When working in the development environment, I would like to be able to import the latest backup file of both databases, for example these:

```
backups/backup_myapp_20251026100000.sql.gz
backups/backup_myapp_logs_20251026000002.sql.gz
```

I then need to adapt the previous command to search for two different backup files (corresponding to two different connections) and then import both of them (again on two different connections):

```php
<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use DatabaseBackup\Utility\BackupImport;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * ImportBackupCommand.
 */
class ImportBackupCommand extends Command
{
    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $hasError = false;

        /** @var string $target */
        $target = Configure::readOrFail('DatabaseBackup.target');

        $Finder = new Finder()
            ->files()
            ->in($target)
            ->sort(fn(SplFileInfo $a, SplFileInfo $b): int => strcmp($b->getRealPath(), $a->getRealPath()));

        foreach (['default', 'logs'] as $connectionName) {
            $Connection = ConnectionManager::get($connectionName);

            /** @var string $databaseName */
            $databaseName = $Connection->config()['database'];

            $cFinder = (clone $Finder)->name('/^backup_' . $databaseName . '_\d{14}\.sql(\.bz2|\.gz)?$/');

            if (!$cFinder->hasResults()) {
                $hasError = true;
                $io->error('No backup files for the `' . $Connection->configName() . '` connection found in `' . $target . '`');
                continue;
            }

            /**
             * It only gets the first backup file.
             *
             * @var \Symfony\Component\Finder\SplFileInfo $File
             */
            $File = array_values(iterator_to_array($cFinder))[0];

            /**
             * Imports the backup file.
             */
            $BackupImport = new BackupImport($Connection)
                ->filename($File->getRealPath());
            $result = $BackupImport->import();
            if (!$result) {
                $hasError = true;
                $io->error('Backup file `' . $File->getRealPath() . '` could not be imported');
                continue;
            }

            $io->success('Backup file `' . $result . '` imported successfully');
        }

        if ($hasError) {
            return static::CODE_ERROR;
        }

        return static::CODE_SUCCESS;
    }
}
```

There are some significant differences from the previous example:

1. a generic `Finder` instance is created, without calling the `name()` method;
2. a `foreach` loop is executed with the names of the two connections;
3. within the single loop, the `Finder` instance is cloned and the `name()` method is executed;
4. the backup file for that connection is imported, taking care to instantiate `BackupImport` by passing the `$Connection` parameter (otherwise it would only use the default one);
5. overall, in case of an error, the `ConsoleIo::error()` method is called (rather than `abort()`), the `$hasError` variable is set and the current loop is interrupted (with `continue`) to skip to the next one. This ensures that the command is not interrupted at the first error (for example, if one of the two backup files is missing or cannot be imported).
