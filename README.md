[![Build Status](https://travis-ci.com/EdisonLabs/database_sanitize.svg?branch=8.x-1.x)](https://travis-ci.com/EdisonLabs/database_sanitize)

# Database Sanitize

## Overview
Provides a set of drush commands to assist in generating an `database.sanitize.yml` file containing all the queries for database sanitization.

### Commands included
- `db-sanitize-analyze (dbsa)` Provides a report on database tables that needs to be verified and possibly sanitized.
- `db-sanitize-generate (dbsg)` generates an `database.sanitize.yml` file for all tables not specified in the sanitize yaml file.

## Usage instructions
By default both commands scan the directories and merge all the `database.sanitize.yml` files into a single YML file in memory and use the content of this merge file to analyse the database. 

You can use the option `--merge-file` to specify a merge file and skip the scan.
This is meant to be used alongside [merge-yml](https://github.com/EdisonLabs/merge-yaml) composer plugin, so that when you build your local environment for a drupal site, an `database.sanitize.merge.yml` file will be generated. This file's path is what you're expected to pass in.

The generated queries for each missing table default to `TRUNCATE TABLE $table`. Developers are expected to assess what content should be sanitized for each table and edit the file accordingly.

### Examples:
To find out how many tables needs to be defined in `database.sanitize.yml` files:
```
drush dbsa
```
```
# Specifying a merge file.
drush dbsa --merge-file=/var/www/SITE/NON-PUBLIC-FOLDER/database.sanitize.merge.yml
```
To get the yml file content for the missing tables to be sanitized:
```
drush dbsg --machine-name="my_module"
```
To save the missing tables to a `database.sanitize.yml` file:
```
drush dbsg --machine-name="MY_profile" > docroot/profiles/MY_profile/database.sanitize.yml
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
