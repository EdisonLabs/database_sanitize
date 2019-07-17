<?php

/**
 * @file
 * Provides procedural version of the service for Drupal 7.
 */

require __DIR__ . '/../vendor/autoload.php';

use EdisonLabs\MergeYaml\MergeYaml;
use Symfony\Component\Yaml\Yaml;

/**
 * Procedural version of DatabaseSanitize::getUnspecifiedTables().
 *
 * @param string $yml_file_path
 *   Optional parameter, the YML file path.
 *
 * @return array
 *   The list of tables not specified in sanitize YAML files.
 */
function database_sanitize_get_unspecified_tables($yml_file_path = NULL) {
  if ($yml_file_path) {
    if (!file_exists($yml_file_path)) {
      throw new \Exception("File does not exist $yml_file_path");
    }

    $file_content = file_get_contents($yml_file_path);
  }
  else {
    $file_content = database_sanitize_get_yml_file_content();
  }

  // Get a list of all tables on the database.
  $db_tables = db_query('show tables')->fetchCol();

  if (empty($file_content)) {
    return $db_tables;
  }

  try {
    $parsed_file = Yaml::parse($file_content);
  }
  catch (ParseException $exception) {
    $message = $exception->getMessage();
    drupal_set_message(t("Unable to parse the sanitize YAML file. @message", ['@message' => $message]), 'error');

    return $db_tables;
  }

  if (is_null($parsed_file) || !array_key_exists('sanitize', $parsed_file)) {
    drupal_set_message(t("The 'sanitize' key is not defined"), 'error');

    return $db_tables;
  }

  if (empty($parsed_file['sanitize'])) {
    return $db_tables;
  }

  $yml_tables = [];
  foreach ($parsed_file['sanitize'] as $machine_name => $tables) {
    foreach ($tables as $table_name => $definition) {
      if (is_array($definition) && !empty(array_filter($definition)) && !array_key_exists('description', $definition)) {
        drupal_set_message(t('Table \'@table_name\' defined by \'@machine_name\' does not specify a \'description\' key', ['@table_name' => $table_name, '@machine_name' => $machine_name]), 'warning');
        continue;
      }

      if (is_array($definition) && !empty(array_filter($definition)) && !array_key_exists('query', $definition)) {
        drupal_set_message(t('Table \'@table_name\' defined by \'@machine_name\' does not specify a \'query\' key', ['@table_name' => $table_name, '@machine_name' => $machine_name]), 'warning');
        continue;
      }

      if (in_array($table_name, $yml_tables)) {
        continue;
      }

      // Support for tables with wildcards in the end.
      if (substr($table_name, -1) == '*') {
        $table_pattern = substr($table_name, 0, -1);
        foreach ($db_tables as $db_table) {
          if (substr($db_table, 0, strlen($table_pattern)) === $table_pattern) {
            array_push($yml_tables, $db_table);
          }
        }
        continue;
      }

      array_push($yml_tables, $table_name);
    }
  }

  $missing = array_diff($db_tables, $yml_tables);
  if (is_array($missing) && empty($missing)) {
    drupal_set_message(t('All database tables are already specified in sanitize YML files'));

    return [];
  }

  sort($missing);

  return $missing;
}

/**
 * Procedural version of DatabaseSanitize::getDatabaseSanitizeYmlFileContent().
 *
 * @return array
 *   The list of tables not specified in sanitize YAML files.
 */
function database_sanitize_get_yml_file_content() {
  $file_content = &drupal_static(__FUNCTION__);

  global $conf;
  $default_locations = [
    DRUPAL_ROOT . '/sites/all/modules',
    DRUPAL_ROOT . '/profiles',
    DRUPAL_ROOT . '/sites/default',
  ];
  $locations = $default_locations;
  if (isset($conf['database_sanitize_source']) && !empty($conf['database_sanitize_source'])) {
    $locations = $conf['database_sanitize_source'];
  }
  $merge_yml = new MergeYaml(["database.sanitize"], $locations, '/tmp');

  if ($file_content) {
    return $file_content;
  }

  $yml_files = $merge_yml->getYamlFiles();

  if (empty($yml_files)) {
    // No valid Yaml files were found.
    return NULL;
  }

  $file_content = $merge_yml->getMergedYmlContent(reset($yml_files));

  return $file_content;
}
