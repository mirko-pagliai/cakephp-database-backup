# DatabaseBackup

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)
[![Build Status](https://api.travis-ci.org/mirko-pagliai/cakephp-database-backup.svg?branch=master)](https://travis-ci.org/mirko-pagliai/cakephp-database-backup)
[![Build status](https://ci.appveyor.com/api/projects/status/imerokwpvy0r51fj/branch/master?svg=true)](https://ci.appveyor.com/project/mirko-pagliai/cakephp-database-backup/branch/master)
[![codecov](https://codecov.io/gh/mirko-pagliai/cakephp-database-backup/branch/master/graph/badge.svg)](https://codecov.io/gh/mirko-pagliai/cakephp-database-backup)

*DatabaseBackup* is a CakePHP plugin to export, import and manage database backups.  
Currently, the plugin supports *MySql*, *Postgres* and *Sqlite* databases.

## Installation
You can install the plugin via composer:

    $ composer require --prefer-dist mirko-pagliai/cakephp-database-backup

**NOTE: the latest version available requires at least CakePHP 3.5**.

Instead, the [cakephp3.2](//github.com/mirko-pagliai/cakephp-database-backup/tree/cakephp3.2)
branch is compatible with all previous versions of CakePHP from version 3.2. 
In this case, you can install the package as well:

    $ composer require --prefer-dist mirko-pagliai/cakephp-database-backup:dev-cakephp3.2
    
After installation, you have to edit `APP/config/bootstrap.php` to load the plugin:

    Plugin::load('DatabaseBackup', ['bootstrap' => true]);

For more information on how to load the plugin, please refer to the 
[Cookbook](http://book.cakephp.org/3.0/en/plugins.html#loading-a-plugin).
    
By default the plugin uses the `APP/backups` directory to save the backups 
files. So you have to create the directory and make it writable:

    $ mkdir backups/ && chmod 775 backups/

If you want to use a different directory, read below.

## Requirements
*DatabaseBackup* requires:
* `mysql` and `mysqldump` for *MySql* databases;
* `pg_dump` and `pg_restore` for *Postgres* databases;
* `sqlite3` for *Sqlite* databases.

**Optionally**, if you want to handle compressed backups, `bzip2` and `gzip` are 
also required.

The installation of these binaries may vary depending on your operating system.

Please forward, remember that the database user must have the correct
permissions (for example, for `mysql` the user must have the `LOCK TABLES`
permission).

## Configuration
The plugin uses some configuration parameters. See our wiki:
* [Configuration](https://github.com/mirko-pagliai/cakephp-database-backup/wiki/Configuration)

If you want to send backup files by email, remember to set up your application
correctly so that it can send emails. For more information on how to configure
your application, see the [Cookbook](https://book.cakephp.org/3.0/en/core-libraries/email.html#configuring-transports).

## How to use
See our wiki:
* [Export backups as cron jobs](https://github.com/mirko-pagliai/cakephp-database-backup/wiki/Export-backups-as-cron-jobs)
* [How to use the BackupExport utility](https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility)
* [How to use the BackupImport utility](https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupImport-utility)
* [How to use the BackupManager utility](https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupManager-utility)
* [How to use the BackupShell](https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupShell)

## Tests
Tests are divided into two groups, `onlyUnix` and `onlyWindows`. This is
necessary because some commands to be executed in the terminal are only valid
for an environment.

By default, phpunit is executed like this:

    vendor/bin/phpunit --exclude-group=onlyWindows

On Windows, it must be done this way:

    vendor\bin\phpunit.bat --exclude-group=onlyUnix

## Versioning
For transparency and insight into our release cycle and to maintain backward 
compatibility, *DatabaseBackup* will be maintained under the 
[Semantic Versioning guidelines](http://semver.org).
