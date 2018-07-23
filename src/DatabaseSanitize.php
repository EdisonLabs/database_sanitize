<?php

namespace Drupal\database_sanitize;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use EdisonLabs\MergeYaml\MergeYaml;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class DatabaseSanitize.
 *
 * @package Drupal\database_sanitize
 */
class DatabaseSanitize {

  const DATABASE_SANITIZE_FILE_NAME = "database.sanitize";

  /**
   * Merge Yaml object.
   *
   * @var \EdisonLabs\MergeYaml\MergeYaml
   *   Object instance.
   */
  protected $mergeYaml;

  /**
   * Logger object.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * DatabaseSanitize constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The LoggerChannelFactoryInterface object.
   *
   * @throws \Exception
   */
  public function __construct(LoggerChannelFactoryInterface $logger) {
    $this->logger = $logger->get('database_sanitize');

    $locations = $this->getSourceLocations();
    $output_dir = $this->getOutputDir();

    $this->mergeYaml = new MergeYaml([self::DATABASE_SANITIZE_FILE_NAME], $locations, $output_dir);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')
    );
  }

  /**
   * Returns the output directory to MergeYaml export the YML merge files.
   *
   * @return string
   *   The output directory path.
   */
  public function getOutputDir() {
    $merge_yaml_config = $this->getMergeYamlConfig();

    if (empty($merge_yaml_config['output-dir'])) {
      return '/tmp';
    }

    return $merge_yaml_config['output-dir'];
  }

  /**
   * Gets the source directories to scan for sanitize YML files.
   *
   * @return array
   *   An array containing the locations.
   */
  public function getSourceLocations() {
    $merge_yaml_config = $this->getMergeYamlConfig();

    $default_locations = [
      DRUPAL_ROOT . '/modules',
      DRUPAL_ROOT . '/profiles',
    ];

    $locations = $default_locations;
    if (!empty($merge_yaml_config['locations'])) {
      $locations = $merge_yaml_config['locations'];
    }

    // @TODO we are assuming here the location of composer.json.
    $composer_file = DRUPAL_ROOT . '/../composer.json';
    $composer_root = dirname($composer_file);

    // Converts paths to be absolute.
    foreach ($locations as &$location) {
      if (!file_exists($location)) {
        $location = realpath("$composer_root/$location");
      }
    }
    unset($location);

    return $locations;
  }

  /**
   * Returns an array containing the Merge Yaml config from composer.json.
   *
   * @return array
   *   The Merge Yaml config.
   */
  public function getMergeYamlConfig() {
    $config = &drupal_static(__FUNCTION__);

    if (isset($config)) {
      return $config;
    }

    // @TODO we are assuming here the location of composer.json.
    $composer_file = DRUPAL_ROOT . '/../composer.json';
    if (!file_exists($composer_file)) {
      return [];
    }

    $composer_file_content = file_get_contents($composer_file);
    $composer_data = Json::decode($composer_file_content);

    $config = [];
    if (isset($composer_data['extra']['merge-yaml'])) {
      $config = $composer_data['extra']['merge-yaml'];
    }

    return $config;
  }

  /**
   * Returns the content of the final database.sanitize.yml file.
   *
   * @return string
   *   The file content.
   */
  public function getDatabaseSanitizeYmlFileContent() {
    $file_content = &drupal_static(__FUNCTION__);

    if ($file_content) {
      return $file_content;
    }

    $yml_files = $this->mergeYaml->getYamlFiles();

    if (empty($yml_files)) {
      // No valid Yaml files were found.
      return NULL;
    }

    $file_content = $this->mergeYaml->getMergedYmlContent(reset($yml_files));

    return $file_content;
  }

  /**
   * Gets a list of tables in the database not specified in sanitize YML files.
   *
   * @param string $yml_file_path
   *   Optional parameter, the YML file path.
   *
   * @return array
   *   The list of tables not specified in sanitize YAML files.
   *
   * @throws \Exception
   */
  public function getUnspecifiedTables($yml_file_path = NULL) {
    if ($yml_file_path) {
      if (!file_exists($yml_file_path)) {
        throw new \Exception("File does not exist $yml_file_path");
      }

      $file_content = file_get_contents($yml_file_path);
    }
    else {
      $file_content = $this->getDatabaseSanitizeYmlFileContent();
    }

    // Get a list of all tables on the database.
    $db_tables = \Drupal::database()->query('show tables')->fetchCol();

    if (empty($file_content)) {
      return $db_tables;
    }

    try {
      $parsed_file = Yaml::parse($file_content);
    }
    catch (ParseException $exception) {
      $message = $exception->getMessage();
      $this->logger->error("Unable to parse the sanitize YAML file. @message", ['@message' => $message]);

      return $db_tables;
    }

    if (is_null($parsed_file) || !array_key_exists('sanitize', $parsed_file)) {
      $this->logger->error("The 'sanitize' key is not defined");

      return $db_tables;
    }

    if (empty($parsed_file['sanitize'])) {
      return $db_tables;
    }

    $yml_tables = [];
    foreach ($parsed_file['sanitize'] as $machine_name => $tables) {
      foreach ($tables as $table_name => $definition) {
        if (is_array($definition) && !array_key_exists('description', $definition)) {
          $this->logger->warning('Table \'@table_name\' defined by \'@machine_name\' does not specify a \'description\' key', ['@table_name' => $table_name, '@machine_name' => $machine_name]);
          continue;
        }

        if (is_array($definition) && !array_key_exists('query', $definition)) {
          $this->logger->warning('Table \'@table_name\' defined by \'@machine_name\' does not specify a \'query\' key', ['@table_name' => $table_name, '@machine_name' => $machine_name]);
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
      $this->logger->info('All database tables are already specified in sanitize YML files');

      return [];
    }

    sort($missing);

    return $missing;
  }

}
