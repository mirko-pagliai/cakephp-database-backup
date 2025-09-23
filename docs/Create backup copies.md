# Create backup copies

- [Introduction](#introduction)
- [Create a command to export (`ExportBackupCommand`)](#create-a-command-to-export---exportbackupcommand--)
- [Create a command to rotate backups (`RotateBackupCommand`)](#create-a-command-to-rotate-backups---rotatebackupcommand--)

## Introduction

As we should know, it is never enough to just create a local backup (as this plugin does).  
Instead, **it is necessary to also create a copy of the backup, to be stored elsewhere**.

Since the PHP functions for the filesystem also work perfectly with the ftp protocol, this guide is also valid for creating the copy on another directory (for example an external disk) or better on another machine or an external server (like a ftp server).

We will integrate two commands into our app:
1) `ExportBackupCommand`, which will be responsible for exporting the backup and making a copy of it on a remote ftp server;
2) `RotateBackupCommand`, which will be responsible for rotating both the backups on the same machine and the copies stored remotely.

In both cases, therefore, we will not use commands directly provided by the plugin, but as mentioned we will implement our own, more effective and customizable, according to our needs.
The second command is essential to maintain only a limited number of backups and copies.

In my specific case, I perform 5 backups per day of the same database.
So I can safely make sure that the second command, which as mentioned will rotate backups and their copies, is instead executed once a day (it is sufficient). Obviously nothing prevents me from doing everything with a single command, but this setup (two separate commands) is better suited to my case, in addition to allowing me to keep the code separate and tidy (and to write more efficient tests).

The example will use the [Filesystem](https://symfony.com/doc/current/components/filesystem.html) and [Finder](https://symfony.com/doc/current/components/finder.html) components provided by Symfony.

It will also use the `DatabaseBackup.copyDirTarget` configuration, which must be set elsewhere, for example:
```php
Configure::write('DatabaseBackup.copyDirTarget', 'ftp://username:password@example.com:21/my/target/copy/dir');
```

It can also be the path of a simple directory (for example an external disk), but if it contains the authentication data to an FTP server as plain text (as in my case), **it is best to set this configuration in a safe place**.

## Create a command to export (`ExportBackupCommand`)

Let's see the `src/Command/ExportBackupCommand.php` file:

```php
<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use DatabaseBackup\Compression;
use DatabaseBackup\Utility\BackupExport;
use Exception;
use Symfony\Component\Filesystem\Filesystem;

/**
 * ExportBackupCommand.
 *
 * Exports a database backup and creates a copy.
 */
class ExportBackupCommand extends Command
{
    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io): void
    {
        $BackupExport = new BackupExport();
        try {
            /** @var string $backup */
            $backup = $BackupExport
                ->compression(Compression::Gzip)
                ->export();
        } catch (Exception $e) {
            $io->abort('`BackupExport` failed with message: "' . $e->getMessage() . '"');
        }

        $io->verbose('Created `' . basename($backup) . '` backup file');

        /** @var string $copyDirTarget */
        $copyDirTarget = Configure::readOrFail('DatabaseBackup.copyDirTarget');
        $targetCopy = rtrim($copyDirTarget, DS) . DS . basename($backup);

        $Filesystem = new Filesystem();
        try {
            $Filesystem->copy($backup, $targetCopy);
        } catch (Exception $e) {
            $io->abort('File copy failed with message: "' . $e->getMessage() . '"');
        }

        $io->verbose('File `' . basename($backup) . '` copied to `' . $targetCopy . '`');
    }
}
```

Now let's understand what this command does.

First of all it exports a database backup using the `BackupExport` utility ([as explained here](How%20to%20use%20the%20BackupExport%20utility.md)), setting only the compression I want.  
This will export the backup to the `backups/` directory of my own app by default.  
Thanks to the `try`/`catch` block, I can catch any exceptions during the backup export, properly stop the command and report the eventual error in the console.

Let's try running this command:
```bash
$ bin/cake export_backup -v
Created `backup_myapp_20250306165116.sql.gz` backup file
File `backup_myapp_20250306165116.sql.gz` copied to `ftp://username:password@example.com:21/my/target/copy/dir`
```

It works as desired and with the `-v` (`--verbose`) option I can get some useful output.

As mentioned, in my specific case this command will be executed 5 times a day, thus creating 5 backups and 5 copies.

## Create a command to rotate backups (`RotateBackupCommand`)

Let's see the `src/Command/RotateBackupCommand.php` file:

```php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * RotateBackupCommand.
 *
 * Rotates database backups and copies of them.
 */
class RotateBackupCommand extends Command
{
    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io): void
    {
        /**
         * Sets a limit to 1 months ago
         */
        $limit = Date::today()->subMonths(1)->toDateString();

        /** @var string $target */
        $target = Configure::readOrFail('DatabaseBackup.target');

        /** @var string $copyDirTarget */
        $copyDirTarget = Configure::readOrFail('DatabaseBackup.copyDirTarget');

        /**
         * It searches for all files older than `$limit`
         */
        $Finder = new Finder();
        $Finder
            ->files()
            // Only `sql`/`sql.gz`/`sql.bz2` files
            ->name('/\.sql(\.(gz|bz2))?$/')
            ->in([$target, $copyDirTarget])
            /** @see https://symfony.com/doc/current/components/finder.html#file-date */
            ->date('< ' . $limit);

        $Filesystem = new Filesystem();

        foreach ($Finder as $File) {
            try {
                $Filesystem->remove($File->getPathname());
            } catch (Exception $e) {
                $io->abort('File deletion failed with message: "' . $e->getMessage() . '"');
            }

            $io->verbose('Removed `' . $File->getPathname() . '` backup file');
        }
    }
}
```

Again, let's understand what this command does.

With the `$limit` variable, we set the limit beyond which to delete backups and copies. In this example, the limit is set to one month ago, but it can be changed as needed. It is important that at the end the `$limit` is a value supported by `strtotime()` (for this reason we start with an instance of `Date` and conclude with the `toDateString()` method).

Then the two directories we are interested in are set, `$target` and `$copyDirTarget`.

So thanks to the [Finder component](https://symfony.com/doc/current/components/finder.html) we recover all the files contained in those directories and which have a date prior to `$limit`.  
Here it is sufficient to get all the files contained in the two directories and then filter them by date as explained, but for more complex cases it is possible to use many other methods that allow you to filter by name, path, size, depth, etc. (refer to the documentation).

Then the resulting files are iterated and deleted once, always through a `try`/`catch` block that allows exceptions to be handled.

Let's try running this command:

```bash
$ bin/cake rotate_backup -v
Removed `/var/www/html/myapp/backups/backup_myapp_20250205130001.sql.gz` backup file
Removed `/var/www/html/myapp/backups/backup_myapp_20250205160001.sql.gz` backup file
Removed `/var/www/html/myapp/backups/backup_myapp_20250205080001.sql.gz` backup file
Removed `/var/www/html/myapp/backups/backup_myapp_20250205220001.sql.gz` backup file
Removed `/var/www/html/myapp/backups/backup_myapp_20250205100001.sql.gz` backup file
Removed `ftp://username:password@example.com:21/my/target/copy/dir/backup_myapp_20250205080001.sql.gz` backup file
Removed `ftp://username:password@example.com:21/my/target/copy/dir/backup_myapp_20250205100001.sql.gz` backup file
Removed `ftp://username:password@example.com:21/my/target/copy/dir/backup_myapp_20250205130001.sql.gz` backup file
Removed `ftp://username:password@example.com:21/my/target/copy/dir/backup_myapp_20250205160001.sql.gz` backup file
Removed `ftp://username:password@example.com:21/my/target/copy/dir/backup_myapp_20250205220001.sql.gz` backup file
```

This also produces the desired result, deleting all files from the thirty-first day (which here were the only ones to exceed the imposed limit).
