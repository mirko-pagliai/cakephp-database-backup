# MysqlBackup

[![Build Status](https://api.travis-ci.org/mirko-pagliai/cakephp-mysql-backup.svg?branch=master)](https://travis-ci.org/mirko-pagliai/cakephp-mysql-backup)
[![Coverage Status](https://img.shields.io/codecov/c/github/mirko-pagliai/cakephp-mysql-backup.svg?style=flat-square)](https://codecov.io/github/mirko-pagliai/cakephp-mysql-backup)

*MysqlBackup* is a CakePHP plugin to export, import and manage database backups.

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
*MysqlBackup* requires `mysql` and `mysqldump`.  
**Optionally**, if you want to handle compressed backups, `bzip2` and `gzip` are 
also required.  
The installation of these binaries may vary depending on your operating system.

## Configuration
The plugin uses some configuration parameters and you can set them using the 
`\Cake\Core\Configure` class. It does not matter if you do it **before** loading 
the plugin.

For example, you can do this at the bottom of the file `APP/config/app.php`
of your application.

### Configuration values
    Configure::write('MysqlBackup.bin.bzip2', '/full/path/to/bzip2');

By default, the `bzip2` binary will be detected automatically, using the 
Unix `which` command.  
If the binary is not found or if you want to set a different path for the 
bynary, you can use `MysqlBackup.bin.bzip2`.  
Note that using `bzip2` is **optional**, but it's required if you want to handle 
compressed backup.

    Configure::write('MysqlBackup.bin.gzip', '/full/path/to/mysql');

By default, the `gzip` binary will be detected automatically, using the 
Unix `which` command.  
If the binary is not found or if you want to set a different path for the 
bynary, you can use `MysqlBackup.bin.gzip`.
Note that using `gzip` is **optional**, but it's required if you want to handle 
compressed backup.

    Configure::write('MysqlBackup.bin.mysql', '/full/path/to/mysql');

By default, the `mysql` binary will be detected automatically, using the 
Unix `which` command.  
If the binary is not found or if you want to set a different path for the 
bynary, you can use `MysqlBackup.bin.mysql`.

    Configure::write('MysqlBackup.bin.mysqldump', '/full/path/to/mysqldump');

By default, the `mysqldump` binary will be detected automatically, using the 
Unix `which` command.  
If the binary is not found or if you want to set a different path for the 
bynary, you can use `MysqlBackup.bin.mysqldump`.

    Configure::write('MysqlBackup.connection', 'default');
    
Setting `MysqlBackup.connection`, you can choose which database connection you
want to use.  
For more information about database connections, please refer to the 
[Cookbook](http://book.cakephp.org/3.0/en/orm/database-basics.html#configuration).

    Configure::write('MysqlBackup.target', ROOT . DS . 'backups');
    
Setting `MysqlBackup.target', you can use another directory where the plugin
will save backup files.

## How to use
See our wiki:
* [How to use the BackupExport utility](https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupExport-utility)
* [How to use the BackupImport utility](https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupImport-utility)
* [How to use the BackupManager utility](https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupManager-utility)
* [How to use the BackupShell](https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupShell)

## Versioning
For transparency and insight into our release cycle and to maintain backward 
compatibility, *Thumbs* will be maintained under the 
[Semantic Versioning guidelines](http://semver.org).
