# cakephp-database-backup

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)
[![Build Status](https://api.travis-ci.org/mirko-pagliai/cakephp-database-backup.svg?branch=master)](https://travis-ci.org/mirko-pagliai/cakephp-database-backup)
[![Build status](https://ci.appveyor.com/api/projects/status/imerokwpvy0r51fj/branch/master?svg=true)](https://ci.appveyor.com/project/mirko-pagliai/cakephp-database-backup/branch/master)
[![codecov](https://codecov.io/gh/mirko-pagliai/cakephp-database-backup/branch/master/graph/badge.svg)](https://codecov.io/gh/mirko-pagliai/cakephp-database-backup)
[![CodeFactor](https://www.codefactor.io/repository/github/mirko-pagliai/cakephp-database-backup/badge)](https://www.codefactor.io/repository/github/mirko-pagliai/cakephp-database-backup)

*DatabaseBackup* is a CakePHP plugin to export, import and manage database backups.
Currently, the plugin supports *MySql*, *Postgres* and *Sqlite* databases.

Did you like this plugin? Its development requires a lot of time for me.
Please consider the possibility of making [a donation](//paypal.me/mirkopagliai):
even a coffee is enough! Thank you.

[![Make a donation](https://www.paypalobjects.com/webstatic/mktg/logo-center/logo_paypal_carte.jpg)](//paypal.me/mirkopagliai)

## Installation
You can install the plugin via composer:

```bash
$ composer require --prefer-dist mirko-pagliai/cakephp-database-backup
```

Then you have to load the plugin. For more information on how to load the plugin,
please refer to the [Cookbook](//book.cakephp.org/4.0/en/plugins.html#loading-a-plugin).

Simply, you can execute the shell command to enable the plugin:
```bash
bin/cake plugin load DatabaseBackup
```
This would update your application's bootstrap method.

By default the plugin uses the `APP/backups` directory to save the backups
files. So you have to create the directory and make it writable:

```bash
$ mkdir backups/ && chmod 775 backups/
```

If you want to use a different directory, read the [Configuration](#configuration) section.

### Installation on older CakePHP and PHP versions
Recent packages and the master branch require at least CakePHP 4.0 and PHP 7.2.
Instead, the [cakephp3](//github.com/mirko-pagliai/cakephp-database-backup/tree/cakephp3) branch
requires at least PHP 5.6.

In this case, you can install the package as well:
```bash
$ composer require --prefer-dist mirko-pagliai/cakephp-database-backup:dev-cakephp3
```

Note that the `cakephp3` branch will no longer be updated as of April 29, 2021,
except for security patches, and it matches the
[2.8.5](//github.com/mirko-pagliai/cakephp-database-backup/releases/tag/2.8.5) version.

## Requirements
*DatabaseBackup* requires:
*   `mysql` and `mysqldump` for *MySql* databases;
*   `pg_dump` and `pg_restore` for *Postgres* databases;
*   `sqlite3` for *Sqlite* databases.

**Optionally**, if you want to handle compressed backups, `bzip2` and `gzip` are
also required.

The installation of these binaries may vary depending on your operating system.

Please forward, remember that the database user must have the correct
permissions (for example, for `mysql` the user must have the `LOCK TABLES`
permission).

## Configuration
The plugin uses some configuration parameters. See our wiki:
*   [Configuration](https://github.com/mirko-pagliai/cakephp-database-backup/wiki/Configuration)

If you want to send backup files by email, remember to set up your application
correctly so that it can send emails. For more information on how to configure
your application, see the [Cookbook](https://book.cakephp.org/4.0/en/core-libraries/email.html#configuring-transports).

## How to use
See our wiki:
*   [Export backups as cron jobs](https://github.com/mirko-pagliai/cakephp-database-backup/wiki/Export-backups-as-cron-jobs)
*   [How to use the BackupExport utility](https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility)
*   [How to use the BackupImport utility](https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupImport-utility)
*   [How to use the BackupManager utility](https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupManager-utility)
*   [How to use the BackupShell](https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupShell)

And refer to our [API](//mirko-pagliai.github.io/cakephp-database-backup).

## Testing
Some tests refer to a specific driver: `mysql`, `postgres`, and `sqlite` groups.
By default, all tests are performed, but you can test only one group or exclude a group using the `--group` and `--exclude-group` options.

For example:
```bash
vendor/bin/phpunit --group mysql
```

## Versioning
For transparency and insight into our release cycle and to maintain backward
compatibility, *DatabaseBackup* will be maintained under the
[Semantic Versioning guidelines](http://semver.org).
