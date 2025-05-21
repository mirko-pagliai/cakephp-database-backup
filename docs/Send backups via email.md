Up until version `2.13.3`, the plugin offered various possibilities to send the created backup files via email.  
All of these (classes, methods, options) **have been deprecated** with version `2.13.4` and **removed** with version `2.14.0`.

## Why were they deprecated?

This plugin was born to allow me to quickly perform and manage my MySql database backups.  
Over time the plugin has expanded, offering both universal functions always related to backups (for example supporting Postgres and Sqlite, which I do not use), and extra functions such as sending by sending.  
Time is short, the desire to continue the development of this plugin (that is updated, functional, correctly written and fast) is strong, for this reason I decided to remove any extra functionality not strictly related to making a backup and delegate these to the end user (who can implement them independently).  
**Sorry**.

## Send backups via email

Let's see how to implement a method that takes care of creating backups and sending them via email.

In these examples the email will be sent from the address `sender@example.com` to the address `recipient@example.com`. The first sender address must of course be properly configured and able to send email.  
Please refer to the `Mailer` class described in the [CakePHP documentation](https://book.cakephp.org/5/en/core-libraries/email.html).

```php
public function sendBackupWithMail(): void
{
    /**
     * First I create a backup normally.
     * `$filename` will be the full path to the created backup.
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility
     */
    $BackupExport = new BackupExport();
    $BackupExport->compression('gzip');
    $filename = $BackupExport->export();

    /**
     * Now I create a `Mailer` instance, which will take care of sending the email.
     * @see https://book.cakephp.org/5/en/core-libraries/email.html#namespace-Cake\Mailer
     */
    $Mailer = new Mailer();
    $Mailer
        ->setFrom('sender@example.com')
        ->setTo('recipient@example.com')
        ->setSubject('This mail contains my backup')
        ->setAttachments([
            basename($filename) => ['file' => $filename, 'mimetype' => mime_content_type($filename)],
        ])
        ->send();
}
```

## Create a command to export and send backups via email

However, the previous example could be inconvenient and impractical to use. Much better, instead, to implement a command that does the same thing (and that can be run from the command line, or set regularly with cronjob).

In your application, create the `src/Command/ExportAndSendBackupCommand.php` file:

```php
<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Mailer\Mailer;
use DatabaseBackup\Utility\BackupExport;
use Exception;

/**
 * This command creates a backup of the database and sends it via email
 */
class ExportAndSendBackupCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io): void
    {
        try {
            /**
             * First I create a backup normally.
             * `$filename` will be the full path to the created backup.
             * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility
             */
            $BackupExport = new BackupExport();
            $BackupExport->compression('gzip');
            $filename = $BackupExport->export();
        } catch (Exception $e) {
            $io->abort('`BackupExport` reported an error: "' . $e->getMessage() . '"');
        }

        $io->success('Backup file `' .$filename . '`has been created');

        /**
         * Now I create a `Mailer` instance, which will take care of sending the email.
         * @see https://book.cakephp.org/5/en/core-libraries/email.html#namespace-Cake\Mailer
         */
        $recipient = 'recipient@example.com';
        try {
            $Mailer = new Mailer();
            $Mailer
                ->setFrom('sender@example.com')
                ->setTo($recipient)
                ->setSubject('This mail contains my backup')
                ->setAttachments([
                    basename($filename) => ['file' => $filename, 'mimetype' => mime_content_type($filename)],
                ])
            ->send();
        } catch (Exception $e) {
            $io->abort('`Mailer` reported an error: "' . $e->getMessage() . '"');
        }

        $io->success('The backup has been sent to `' . $recipient . '`');
    }
}
```

The command also takes care of catching any exceptions thrown by `BackupExport` (during backup creation) or by `Mailer` (during email sending), returning a valid error to the console.

Now you can run the command in the console:

```bash
$ bin/cake export_and_send_backup
```

If everything went well, the output should be:

```bash
$ bin/cake export_and_send_backup
Backup file `/home/mirko/Server/my_app/backups/backup_climat_20250213165932.sql.gz`has been created
The backup has been sent to `recipient@example.com`
```

Finally, you can set this command as a cron job, so that it runs at predefined intervals (see [Running Shells as Cron Jobs](https://book.cakephp.org/5/en/console-commands/cron-jobs.html#running-shells-as-cron-jobs)).