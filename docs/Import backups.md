# Import backups

> [!WARNING]  
> Please note: These backup import commands are for illustrative purposes only.  
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
