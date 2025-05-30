# cakephp-database-backup

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)
[![CI](https://github.com/mirko-pagliai/cakephp-database-backup/actions/workflows/ci.yml/badge.svg?branch=3.0.x)](https://github.com/mirko-pagliai/cakephp-database-backup/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/mirko-pagliai/cakephp-database-backup/branch/3.0.x/graph/badge.svg?token=nkaJk4nvus)](https://codecov.io/gh/mirko-pagliai/cakephp-database-backup)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/cd12284c1047431c8149e09fa56536bf)](https://app.codacy.com/gh/mirko-pagliai/cakephp-database-backup/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_grade)
[![CodeFactor](https://www.codefactor.io/repository/github/mirko-pagliai/cakephp-database-backup/badge)](https://www.codefactor.io/repository/github/mirko-pagliai/cakephp-database-backup)

*DatabaseBackup* is a CakePHP plugin to export, import and manage database backups.
Currently, the plugin supports *MySql*, *Postgres* and *Sqlite* databases.

Did you like this plugin? Its development requires a lot of time for me.
Please consider the possibility of making [a donation](//paypal.me/mirkopagliai):
even a coffee is enough! Thank you.

[![Make a donation](https://www.paypalobjects.com/webstatic/mktg/logo-center/logo_paypal_carte.jpg)](//paypal.me/mirkopagliai)

## Requirements

*DatabaseBackup* requires:

*   `mariadb` and `mariadb-dump` for *MariaDB*/*MySql* databases (if you still use `mysql`/`mysqldump`, [see here](https://github.com/mirko-pagliai/cakephp-database-backup/blob/3.0.x/docs/Common%20issues.md#transition-from-mysql-and-mysqldump-to-mariadb-and-mariadb-dump));
*   `pg_dump` and `pg_restore` for *Postgres* databases;
*   `sqlite3` for *Sqlite* databases.

**Optionally**, if you want to handle compressed backups, `bzip2` and `gzip` are
also required.

The installation of these binaries may vary depending on your operating system.

## Installation

You can install the plugin via composer:

```bash
$ composer require --prefer-dist mirko-pagliai/cakephp-database-backup
```

Then you have to load the plugin. For more information on how to load the plugin,
please refer to the [CakePHP documentation](https://book.cakephp.org/5/en/plugins.html#loading-a-plugin).

You can execute the shell command to enable the plugin:
```bash
$ bin/cake plugin load DatabaseBackup
```
This would update your application's bootstrap method.

### Installation on older CakePHP and PHP versions

Compared to the current installation requirements, some tags or branches are provided for those using older versions of
CakePHP and PHP:

- `2.15` versions, which require at least CakePHP `5.0` and PHP `8.1`;
- tag [`cakephp4`](https://github.com/mirko-pagliai/cakephp-database-backup/releases/tag/cakephp4), which requires at
  least PHP `>=7.4.0` and CakePHP `^4.0`.   
  This tag no longer receives any updates as of January 5, 2024, and roughly coincides with what `2.12.3` version was.
- tag [`cakephp3`](https://github.com/mirko-pagliai/cakephp-database-backup/releases/tag/cakephp3), which requires at
  least PHP `>=5.6 <7.4` and CakePHP `^3.5.1`.   
  This tag no longer receives any updates as of April 29, 2021, and roughly coincides with what `2.8.5` version was.

You can freely use these, even by downloading the source codes from the attached assets, but their functioning is
no longer guaranteed, especially regarding old dependencies that may no longer be available.

## Configuration and How to use

See [our wiki](/docs).

Before opening an issue, check this list of [common issues](docs/Common%20issues.md).

## Testing

Unlike previous versions, with the 3.x branch, thanks to the Mockery's overloading and the (external) component `Process` that actually takes care of executing the commands to export/import the databases, normally the tests do not really use the database drivers and do not actually write or read files on the filesystem (i.e. everything is simulated).

The only exception is given by the class `DatabaseBackup\Test\TestCase\Utility\BackupExportAndImportTest`, which however does not belong to the testsuite executed by default (it is therefore an optional test) and is marked with the attribute `#[CoversNothing]`.

This test class, when executed, will test a real database export and import, using the PHP extensions `sqlite3`, `mysqli` and `pgsql`, that is all the drivers and databases supported by the plugin.

You can test the class directly or the configured `real-drivers` testsuite ([see available testsuites](phpunit.xml.dist)):
```bash
$ vendor/bin/phpunit --testsuite=real-drivers
```

[Continuous integration (CI) workflows](https://github.com/mirko-pagliai/cakephp-database-backup/actions/workflows/ci.yml) must be run with `highest`/`lowest` dependencies and "without"/"only with" real drivers and databases.

## Versioning

For transparency and insight into our release cycle and to maintain backward
compatibility, *DatabaseBackup* will be maintained under the
[Semantic Versioning guidelines](http://semver.org).
