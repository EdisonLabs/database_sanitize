<?php

namespace Drupal\database_sanitize\Commands;

use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class DatabaseSanitizeCommands extends DrushCommands {

  /**
   * Compares existing database.sanitize.yml files on the site installation against existing database tables.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases, config, etc.
   *
   * @option file
   *   The full path to a sanitize YML file.
   * @option list
   *   List the table names.
   *
   * @command db:sanitize-analyze
   * @aliases dbsa,db-sanitize-analyze
   */
  public function sanitizeAnalyze(array $options = ['file' => NULL, 'list' => NULL]) {
    // See bottom of https://weitzman.github.io/blog/port-to-drush9 for details on what to change when porting a
    // legacy command.
  }

  /**
   * Generates a database.sanitize.yml file for tables not specified on sanitize YML files.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases, config, etc.
   *
   * @option file
   *   The full path to a sanitize YML file.
   * @option machine-name
   *   The machine name to export the tables under.
   *
   * @command db:sanitize-generate
   * @aliases dbsg,db-sanitize-generate
   */
  public function sanitizeGenerate(array $options = ['file' => NULL, 'machine-name' => NULL]) {
    // See bottom of https://weitzman.github.io/blog/port-to-drush9 for details on what to change when porting a
    // legacy command.
  }

}
