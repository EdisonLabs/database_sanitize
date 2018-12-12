[![Build Status](https://travis-ci.com/EdisonLabs/database_sanitize.svg?branch=7.x-1.x)](https://travis-ci.com/EdisonLabs/database_sanitize)

# Database Sanitize

## Overview
Provides a set of drush commands to assist in generating a `database.sanitize.yml` file containing all the queries for database sanitization.

### Commands included
- `db-sanitize-analyze (dbsa)` Compares existing `database.sanitize.yml` files on the site installation against existing database tables and list tables that needs to be verified and possibly sanitized.
- `db-sanitize-generate (dbsg)` generates a `database.sanitize.yml` file for all tables not specified in `database.sanitize.yml` files.

Use the option `--file` to specify a YML file and skip the scan.
This is meant to be used alongside [merge-yaml](https://github.com/EdisonLabs/merge-yaml) composer plugin, so that when you build your local environment for a drupal site, an `database.sanitize.merge.yml` file will be generated. This file's path is what you're expected to pass in.

## Installation instructions
To install, there are two options:
- If you want it available for all sites, place the `database_sanitize` folder on your `~/.drush` folder.
- If you only want it on one site, place the `database_sanitize` folder on the `sites/all/modules/contrib` folder of that site.

After that, run `drush cc drush` so the command is registered with drush.

### Configuration
The commands by default will scan for `database.sanitize.yml` files on the following directories:
```
/sites/all/modules
/profiles
/sites/default
```
You can configure different directories by setting an array of directories relative to `DRUPAL_ROOT` on the configuration variable `database_sanitize_source`:
```
variable_set('database_sanitize_source', array('/MYCUSTOM_DIRECTORY'));
```
Or alternatively on your `settings.php` file:
```
$conf['database_sanitize_source'] = array(
  'sites/all/modules',
  'profiles',
);
```

## Usage instructions
You can provide a `database.sanitize.yml` file containing queries for database sanitization for your module or profile.

__File format__
```
sanitize:
    MACHINE_NAME:
        DBTABLENAME1:
            description: 'query description'
            query: 'DB QUERY 1'
        DBTABLENAME2:
            description: 'query description'
            query: 'DB QUERY 2'
    MACHINE_NAME2:
        DBTABLENAME3:
            description: 'query description'
            query: 'DB QUERY 3'
```

### Commands usage
To find out how many tables needs to be defined in `database.sanitize.yml` files:
```
drush dbsa
```
```
# Specifying a file.
drush dbsa --file=/var/www/SITE/NON-PUBLIC-FOLDER/database.sanitize.merge.yml
```
To get the YAML file content for the missing tables to be sanitized:
```
drush dbsg --machine-name="my_module"
```
To save the missing tables to a `database.sanitize.yml` file:
```
drush dbsg --machine-name="MY_profile" > docroot/profiles/MY_profile/database.sanitize.yml
```
The generated queries for each missing table default to `TRUNCATE TABLE $table`. Developers are expected to assess what content should be sanitized for each table and edit the file accordingly.

## Automated Tests and Code Sniffer
This repository is integrated with [Travis CI](https://travis-ci.com/EdisonLabs/database_sanitize) to perform tests and detect Drupal coding standards violations.

## Development
### Running tests locally
You will need to:
1. Run composer install.
2. Run composer install inside the `vendor/drush/drush` directory.
3. Within the root of the package, run this command adjusting `UNISH_DB_URL` with your database configuration.
```
UNISH_DRUPAL_MAJOR_VERSION=7 UNISH_DB_URL="mysql://USERNAME:PASSWORD@127.0.0.1" UNISH_NO_TIMEOUTS=y vendor/drush/drush/vendor/bin/phpunit --configuration "vendor/drush/drush/tests" drush/tests/
```

### Composer manager support
Currently this module is distributed with the Composer `/vendor` folder containing the required packages.
This is necessary because the Composer plugin [merge-yaml](https://github.com/EdisonLabs/merge-yaml) required by the module is incompatible with the version of Composer provided by [Drupal composer](https://www.drupal.org/project/composer) module.
See: https://github.com/EdisonLabs/database_sanitize/issues/10.

#### Updating the packages
To update the packages, remove the suffix `.dev` from the `composer.json.dev` and `composer.lock.dev` files and run `composer update --no-dev` in the module's folder. After the update add the suffix `.dev` back to the files and commit all the changes.

