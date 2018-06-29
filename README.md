[![Build Status](https://travis-ci.com/EdisonLabs/database_sanitize.svg?branch=8.x-1.x)](https://travis-ci.com/EdisonLabs/database_sanitize)

# database_sanitize

## Overview
Provides a set of drush commands to assist in generating an `database.sanitize.yml` file including all tables in the database that are missing from the generated `database.sanitize.merge.yml` file.

### Commands included
- `db-sanitize-analyze (dbsa)` Provides a report on database tables not defined in the specified merged yml file.
- `db-sanitize-generate (dbsg)` generates an `database.sanitize.yml` file for all tables not specified in the merged yaml file.

## Usage instructions
Both commands require the path to the merged sanitize file as the `--merge-file` option:
This is meant to be used alongside [merge-yml](https://github.com/EdisonLabs/merge-yaml) composer plugin, so that when you build your local environment for a drupal site, an `database.sanitize.merge.yml` file will be generated. This file's path is what you're expected to pass in.

The generated queries for each missing table default to `TRUNCATE TABLE $table`. Developers are expected to assess what content should be sanitized for each table and edit the file accordingly.

### Examples:
To find out how many tables are missing from the merge file:
```
drush dbsa --merge-file=/var/www/SITE/NON-PUBLIC-FOLDER/database.sanitize.merge.yml
```
To get the yml file content for the missing tables to be sanitized:
```
drush dbsg --merge-file=/var/www/SITE/NON-PUBLIC-FOLDER/database.sanitize.merge.yml --machine-name="my_module"
```
To save the missing tables yml file:
```
drush dbsg --merge-file=/var/www/pfenplatform/NON-PUBLIC-FOLDER/database.sanitize.merge.yml --machine-name="MY_profile" > app/profiles/MY_profile/database.sanitize.yml
```

## Format of the yml files
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

## Automated Tests and Code Sniffer
This repository is integrated with [Travis CI](https://travis-ci.com/EdisonLabs/database_sanitize) to perform tests and detect Drupal coding standards violations.
