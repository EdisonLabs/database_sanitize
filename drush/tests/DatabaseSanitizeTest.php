<?php

namespace Unish;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * PHPUnit Tests for Database Sanitize.
 *
 * This uses Drush's own test framework,
 * based on PHPUnit.
 *
 * @group commands
 */
class DatabaseSanitizeCase extends CommandUnishTestCase {

  /**
   * The drush site options.
   *
   * @var array
   */
  protected $siteOptions;

  /**
   * The root of the test site installation.
   *
   * @var string
   */
  protected $webRoot;

  /**
   * The list of tables defined in the database.
   *
   * @var array
   */
  protected $dbTables;

  /**
   * The path to a sanitize yml file specifying all tables in the database.
   *
   * @var string
   */
  protected $fullySpecifiedYmlFile;

  /**
   * The path to the merge sanitize yml file.
   *
   * @var string
   */
  protected $mergeYmlFile;

  /**
   * Setup the environment.
   */
  public function setUp() {
    // Install the standard install profile.
    $sites = $this->setUpDrupal(1, TRUE, UNISH_DRUPAL_MAJOR_VERSION);
    $this->webRoot = $this->webroot();
    $this->siteOptions = [
      'root' => $this->webRoot,
      'uri' => key($sites),
      'yes' => NULL,
    ];

    // Symlink database_sanitize inside the site being tested, so that it is
    // available as a drush command.
    $target = dirname(__DIR__, 2);
    \symlink($target, $this->webRoot . '/modules/database_sanitize');

    $this->drush('cache-clear', ['drush'], $this->siteOptions);
    $this->drush('pm-enable', ['database_sanitize', 'node'], $this->siteOptions);

    // Get tables defined in the database.
    $this->drush('sqlq', ['show tables;'], $this->siteOptions);
    $this->dbTables = $this->getOutputAsList();

    $this->fullySpecifiedYmlFile = $this->webRoot . '/database.sanitize.full.yml';
    $this->generateFullySpecifiedYmlFile();

    $this->mergeYmlFile = $this->webRoot . '/modules/database_sanitize/drush/tests/assets/database.sanitize.merge.yml';
  }

  /**
   * Tests Database Sanitize drush commands.
   */
  public function testDatabaseSanitizeCommands() {
    // @see assets/database.sanitize.merge.yml
    $this->assertContains('users', $this->dbTables);
    $this->drush('sqlq', ['show tables like "node_revision%";'], $this->siteOptions);
    $wildcard_tables = $this->getOutputAsList();

    // Test db-sanitize-analyze command.
    $analyze_options = $this->siteOptions + [
      'file' => $this->mergeYmlFile,
    ];

    $dumped_tables_expected = count($this->dbTables) - (1 + count($wildcard_tables));
    $this->drush('db-sanitize-analyze', [], $analyze_options);
    $eds_analyze_output = $this->getErrorOutput();
    $this->assertContains(sprintf('There are %s tables not defined on sanitize YML files', $dumped_tables_expected), $eds_analyze_output);

    $this->assertFileExists($this->fullySpecifiedYmlFile);
    $analyze_options['file'] = $this->fullySpecifiedYmlFile;
    $this->drush('db-sanitize-analyze', [], $analyze_options);
    $this->assertContains('All database tables are already specified', $this->getErrorOutput());

    // Test db-sanitize-generate command.
    $generate_options = $this->siteOptions + [
      'machine-name' => 'database_sanitize_test',
      'file' => $this->mergeYmlFile,
    ];
    $this->drush('db-sanitize-generate', [], $generate_options);
    $yaml = $this->getOutput();
    try {
      $parsed_yaml = Yaml::parse($yaml);
    }
    catch (ParseException $exception) {
      $this->fail(sprintf("Unable to parse the output as YAML: %s", $exception->getMessage()));
    }
    $this->assertArrayHasKey('sanitize', $parsed_yaml);
    $this->assertArrayHasKey('database_sanitize_test', $parsed_yaml['sanitize']);
    // @see assets/database.sanitize.merge.yml
    $this->assertArrayNotHasKey('users', $parsed_yaml['sanitize']['database_sanitize_test']);

    $generate_options['file'] = $this->fullySpecifiedYmlFile;
    $this->drush('db-sanitize-generate', [], $generate_options);
    $this->assertContains('All database tables are already specified', $this->getErrorOutput());
  }

  /**
   * Generates a yml file specifying all tables in the database.
   */
  public function generateFullySpecifiedYmlFile() {
    $content = [
      'sanitize' => [],
    ];
    foreach ($this->dbTables as $table) {
      $content['sanitize']['database_generate_test'][$table] = [
        'description' => '',
        'query' => "TRUNCATE TABLE {$table}",
      ];
    }

    $export = Yaml::dump($content, PHP_INT_MAX, 2);

    file_put_contents($this->fullySpecifiedYmlFile, $export);
  }

}
