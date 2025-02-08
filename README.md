# cakephp-database-backup

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)
[![CI](https://github.com/mirko-pagliai/cakephp-database-backup/actions/workflows/ci.yml/badge.svg)](https://github.com/mirko-pagliai/cakephp-database-backup/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/mirko-pagliai/cakephp-database-backup/branch/master/graph/badge.svg)](https://codecov.io/gh/mirko-pagliai/cakephp-database-backup)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/cd12284c1047431c8149e09fa56536bf)](https://www.codacy.com/gh/mirko-pagliai/cakephp-database-backup/dashboard?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=mirko-pagliai/cakephp-database-backup&amp;utm_campaign=Badge_Grade)
[![CodeFactor](https://www.codefactor.io/repository/github/mirko-pagliai/cakephp-database-backup/badge)](https://www.codefactor.io/repository/github/mirko-pagliai/cakephp-database-backup)

*DatabaseBackup* is a CakePHP plugin to export, import and manage database backups.
Currently, the plugin supports *MySql*, *Postgres* and *Sqlite* databases.

Did you like this plugin? Its development requires a lot of time for me.
Please consider the possibility of making [a donation](//paypal.me/mirkopagliai):
even a coffee is enough! Thank you.

[![Make a donation](https://www.paypalobjects.com/webstatic/mktg/logo-center/logo_paypal_carte.jpg)](//paypal.me/mirkopagliai)

  * [Requirements](#requirements)
  * [Installation](#installation)
    + [Installation on older CakePHP and PHP versions](#installation-on-older-cakephp-and-php-versions)
  * [Configuration](#configuration)
  * [How to use](#how-to-use)
  * [Testing](#testing)
  * [Versioning](#versioning)

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

## Installation
You can install the plugin via composer:

```bash
$ composer require --prefer-dist mirko-pagliai/cakephp-database-backup
```

Then you have to load the plugin. For more information on how to load the plugin,
please refer to the [Cookbook](//book.cakephp.org/4.0/en/plugins.html#loading-a-plugin).

Simply, you can execute the shell command to enable the plugin:
```bash
$ bin/cake plugin load DatabaseBackup
```
This would update your application's bootstrap method.

By default, the plugin uses the `APP/backups` directory to save the backups
files. So you have to create the directory and make it writable:

```bash
$ mkdir backups/ && chmod 775 backups/
```

If you want to use a different directory, read the [Configuration](#configuration) section.

### Installation on older CakePHP and PHP versions

Compared to the current installation requirements, some tags are provided for those using older versions of CakePHP and
PHP (until February 7, 2025, they were available as branches, now only as tags):

- tag [`cakephp4`](https://github.com/mirko-pagliai/cakephp-database-backup/releases/tag/cakephp4), which requires at
least PHP `>=7.4.0` and CakePHP `^4.0`.   
  This tag no longer receives any updates as of January 5, 2024, and roughly coincides with what `2.12.3` version was.
- tag [`cakephp3`](https://github.com/mirko-pagliai/cakephp-database-backup/releases/tag/cakephp3), which requires at
least PHP `>=5.6 <7.4` and CakePHP `^3.5.1`.   
  This tag no longer receives any updates as of April 29, 2021, and roughly coincides with what `2.8.5` version was.

You can freely use these tags, even by downloading the source codes from the attached assets, but their functioning is
no longer guaranteed, especially regarding old dependencies that may no longer be available.

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
Tests are run for only one driver at a time, by default `mysql`.
To choose another driver to use, you can set the `driver_test` environment variable before running `phpunit`.

For example:
```bash
driver_test=sqlite vendor/bin/phpunit
driver_test=postgres vendor/bin/phpunit
```

Alternatively, you can set the `db_dsn` environment variable, indicating the connection parameters. In this case, the driver type will still be detected automatically.

For example:
```bash
db_dsn=sqlite:///' . TMP . 'example.sq3 vendor/bin/phpunit
```

## Versioning
For transparency and insight into our release cycle and to maintain backward
compatibility, *DatabaseBackup* will be maintained under the
[Semantic Versioning guidelines](http://semver.org).
