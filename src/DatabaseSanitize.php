<?php

namespace Drupal\database_sanitize;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use EdisonLabs\MergeYaml\MergeYaml;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DatabaseSanitize.
 *
 * @package Drupal\database_sanitize
 */
class DatabaseSanitize {

  const DATABASE_SANITIZE_FILE_NAME = "database.sanitize.yml";

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

    $merge_yaml_config = $this->getMergeYamlConfig();

    if (empty($merge_yaml_config)) {
      $this->logger->error("Merge Yaml library seems to not be configured correctly.");
    }

    $database_sanitize_file = self::DATABASE_SANITIZE_FILE_NAME;

    // Converts paths to be absolute.
    // @TODO we are assuming here the location of composer.json.
    $composer_file = DRUPAL_ROOT . '/../composer.json';
    $composer_root = dirname($composer_file);

    $locations = $merge_yaml_config['locations'];
    foreach ($locations as &$location) {
      if (!file_exists($location)) {
        $location = realpath("$composer_root/$location");
      }
    }
    unset($location);

    $output_dir = $merge_yaml_config['output-dir'];

    $this->mergeYaml = new MergeYaml([$database_sanitize_file], $locations, $output_dir);
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
   * Returns an array containing the Merge Yaml config from composer.json.
   *
   * @return array
   *   The Merge Yaml config.
   */
  public function getMergeYamlConfig() {
    // @TODO we are assuming here the location of composer.json.
    $composer_file = DRUPAL_ROOT . '/../composer.json';
    if (!file_exists($composer_file)) {
      $this->logger->error("Unable to load composer.json file.");
      return [];
    }

    $composer_file_content = file_get_contents($composer_file);
    $composer_data = Json::decode($composer_file_content);
    if (!isset($composer_data['extra']) || empty($composer_data['extra'])) {
      $this->logger->error("Unable to load extra settings from composer.json.");
      return [];
    }

    $extra = $composer_data['extra'];
    if (!isset($extra['merge-yaml'])) {
      $this->logger->error("Unable to load merge-yaml settings from composer.json.");
      return [];
    }

    if (empty($extra['merge-yaml']['locations'])) {
      $this->logger->error("Unable to load merge-yaml locations settings from composer.json.");
      return [];
    }

    if (empty($extra['merge-yaml']['output-dir'])) {
      $this->logger->error("Unable to load merge-yaml output-dir settings from composer.json.");
      return [];
    }

    return $extra['merge-yaml'];
  }

  /**
   * Returns the content of the database.sanitize.yml file.
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
   * Gets the list of tables in the database not specified in $yml_file_path.
   *
   * @param string $yml_file_path
   *   The yml file path.
   *
   * @return array|false
   *   The list of tables not specified in the yml file or false if error.
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
      $this->logger->error("Unable to parse the file @file as YAML", ["@file" => $yml_file_path]);
      return FALSE;
    }

    // Find tables existing on the database that are not defined in the sanitize
    // yaml file.
    if (!array_key_exists('sanitize', $parsed_file)) {
      $this->logger->error("The file @file does not define an 'sanitize' key", ["@file" => $yml_file_path]);
      return FALSE;
    }

    if (empty($parsed_file['sanitize'])) {
      return $db_tables;
    }

    $yml_tables = [];
    foreach ($parsed_file['sanitize'] as $machine_name => $tables) {
      foreach ($tables as $table_name => $definition) {
        if (!array_key_exists('description', $definition)) {
          $this->logger->warning('Table \'@table_name\' defined by \'@machine_name\' does not specify a \'description\' key.', ['@table_name' => $table_name, '@machine_name' => $machine_name]);
          continue;
        }

        if (!array_key_exists('query', $definition)) {
          $this->logger->warning('Table \'@table_name\' defined by \'@machine_name\' does not specify a \'query\' key.', ['@table_name' => $table_name, '@machine_name' => $machine_name]);
          continue;
        }

        array_push($yml_tables, $table_name);
      }
    }

    $missing = array_diff($db_tables, $yml_tables);
    if (is_array($missing) && empty($missing)) {
      $this->logger->info('All database tables are already specified in @file.', ['@file' => $yml_file_path]);

      return FALSE;
    }

    $skipped = array_diff($yml_tables, $missing);
    foreach ($skipped as $table_name) {
      $this->logger->notice('Database table \'@table_name\' was already specified.', ['@table_name' => $table_name]);
    }

    sort($missing);

    return $missing;
  }

}
