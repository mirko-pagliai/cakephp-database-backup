# MySQLBackup

[![Build Status](https://api.travis-ci.org/mirko-pagliai/cakephp-mysql-backup.svg?branch=master)](https://travis-ci.org/mirko-pagliai/cakephp-mysql-backup)
[![Coverage Status](https://img.shields.io/codecov/c/github/mirko-pagliai/cakephp-mysql-backup.svg?style=flat-square)](https://codecov.io/github/mirko-pagliai/cakephp-mysql-backup)

*MySQLBackup* is a CakePHP plugin to export, import and manage database backups.

## Installation
You can install the plugin via composer:

    $ composer require --prefer-dist mirko-pagliai/cakephp-mysql-backup
    
Then you have to edit `APP/config/bootstrap.php` to load the plugin:

    Plugin::load('MysqlBackup', ['bootstrap' => true]);

For more information on how to load the plugin, please refer to the 
[Cookbook](http://book.cakephp.org/3.0/en/plugins.html#loading-a-plugin).
    
By default the plugin uses the `APP/backups` directory to save the backups 
files. So you have to create the directory and make it writable:

    $ mkdir backups/ && chmod 775 backups/

If you want to use a different directory, read below.

## Requirements
*MySQLBackup* requires `mysql` and `mysqldump`.  
**Optionally**, if you want to handle compressed backups, `bzip2` and `gzip` are 
also required.  
The installation of these binaries may vary depending on your operating system.

## Configuration
The plugin uses some configuration parameters. See our wiki:
* [Configuration](https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/Configuration)

If you want to send backup files by email, remember to set up your application
correctly so that it can send emails. For more information on how to configure
your application, see the [Cookbook](https://book.cakephp.org/3.0/en/core-libraries/email.html#configuring-transports).

## How to use
See our wiki:
* [How to use the BackupExport utility](https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupExport-utility)
* [How to use the BackupImport utility](https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupImport-utility)
* [How to use the BackupManager utility](https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupManager-utility)
* [How to use the BackupShell](https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupShell)

## Versioning
For transparency and insight into our release cycle and to maintain backward 
compatibility, *MySQLBackup* will be maintained under the 
[Semantic Versioning guidelines](http://semver.org).
