# cakephp-database-backup

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)
[![CI](https://github.com/mirko-pagliai/cakephp-database-backup/actions/workflows/ci.yml/badge.svg?branch=2.15.x)](https://github.com/mirko-pagliai/cakephp-database-backup/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/mirko-pagliai/cakephp-database-backup/graph/badge.svg?token=nkaJk4nvus)](https://codecov.io/gh/mirko-pagliai/cakephp-database-backup)
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

* `mariadb` and `mariadb-dump` for *MariaDB*/*MySql* databases (previously `mysql` and `mysqldump`);
* `pg_dump` and `pg_restore` for *Postgres* databases;
* `sqlite3` for *Sqlite* databases.

**Optionally**, if you want to handle compressed backups, `bzip2` and `gzip` are
also required.

The installation of these binaries may vary depending on your operating system.

## Installation

You can install the plugin via composer:

```bash
composer require --prefer-dist mirko-pagliai/cakephp-database-backup
```

Then you have to load the plugin. For more information on how to load the plugin,
please refer to the [CakePHP documentation](https://book.cakephp.org/5/en/plugins.html#loading-a-plugin).

Simply, you can execute the shell command to enable the plugin:
```bash
bin/cake plugin load DatabaseBackup
```
This would update your application's bootstrap method.

By default, the plugin uses the `APP/backups` directory to save the backups
files. So you have to create the directory and make it writable:

```bash
mkdir backups/ && chmod 775 backups/
```

If you want to use a different directory, read the [Configuration](docs/Configuration.md) page.

### Package versions and installation based on CakePHP/PHP version

| Branch                                                                                           | Status                                                                            | Requires CakePHP | Requires PHP |
|--------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------| --- | --- |
| `3.1.x`                                                                                          | In development                                                                    |           |         |
| `3.0.x`                                                                                          | **Current branch**                                                                | `^5.2.10` | `>=8.4` |
| `2.15.x`                                                                                         | Only security updates and relevant bugs                                           | `^5.0` | `>=8.1` |
| `2.14.x`                                                                                         | Abandoned on May 30, 2025                                                         | `^5.0`       | `>=8.1`        |
| tag [`cakephp4`](https://github.com/mirko-pagliai/cakephp-database-backup/releases/tag/cakephp4) | Abandoned on January 5, 2024, and roughly coincides with what `2.12.3` version was | `^4.0` | `>=7.4.0` |
| tag [`cakephp3`](https://github.com/mirko-pagliai/cakephp-database-backup/releases/tag/cakephp3) | Abandoned April 29, 2021, and roughly coincides with what `2.8.5` version was     | `^3.5.1` | `>=5.6 <7.4` | 

You are free to use the `cakephp4` and `cakephp3` tags, even by downloading the source codes from the attached assets,
but their functioning is no longer guaranteed, especially regarding old dependencies that may no longer be available.

## Configuration and How to use

See [our wiki](/docs).

Before opening an issue, check this list of [common issues](docs/Common%20issues.md).

## Testing

Normally tests are only run on one driver at a time, by default `mysql`.

To run tests with another driver, you can use the scripts defined in `composer.json`:

For example:
```bash
composer test
```
```bash
composer test-postgres
```
```bash
composer test-sqlite
```

Instead, to run tests with all drivers, you can use the `test-all` script (individual tests are chained together).

For individual drivers, alternatively you can set the `db_dsn` environment variable, indicating the connection
  parameters. In this case, the driver type will still be detected automatically.

For example:
```bash
db_dsn=sqlite:///' . TMP . 'example.sq3 vendor/bin/phpunit
```

## Versioning

For transparency and insight into our release cycle and to maintain backward
compatibility, *DatabaseBackup* will be maintained under the
[Semantic Versioning guidelines](http://semver.org).
