# MySqlBackup

[![Build Status](https://api.travis-ci.org/mirko-pagliai/cakephp-mysql-backup.svg?branch=master)](https://travis-ci.org/mirko-pagliai/cakephp-mysql-backup)
[![Coverage Status](https://img.shields.io/codecov/c/github/mirko-pagliai/cakephp-mysql-backup.svg?style=flat-square)](https://codecov.io/github/mirko-pagliai/cakephp-mysql-backup)

*MySqlBackup* is a CakePHP plugin to export, import and manage database backups.

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

## Configuration
The plugin uses some configuration parameters and you can set them using the 
`\Cake\Core\Configure` class. It does not matter if you do it *before* loading 
the plugin.

For example, you can do this at the bottom of the file `APP/config/app.php`
of your application.

### Configuration values

    Configure::write('MysqlBackup.connection', 'default');
    
Setting `MysqlBackup.connection`, you can choose which database connection you
want to use.  
For more information about database connections, please refer to the 
[Cookbook](http://book.cakephp.org/3.0/en/orm/database-basics.html#configuration).

    Configure::write('MysqlBackup.target', ROOT . 'backups');
    
Setting `MysqlBackup.target', you can use another directory where the plugin will 
save backup files.

## Versioning
For transparency and insight into our release cycle and to maintain backward 
compatibility, *Thumbs* will be maintained under the 
[Semantic Versioning guidelines](http://semver.org).
