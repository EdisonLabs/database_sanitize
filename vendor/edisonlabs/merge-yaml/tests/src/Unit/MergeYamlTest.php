<?php

namespace EdisonLabs\MergeYaml\Unit;

use EdisonLabs\MergeYaml\MergeYaml;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests generation of merge-yaml.
 */
class MergeYamlTest extends TestCase
{
    /**
   * The directory where the merged files will be placed.
   *
   * @var string
   */
    public $outputDir;

    /**
     * The file name patterns to scan for.
     *
     * @var array
     */
    public $fileNamePatterns;

    /**
     * The paths to scan recursively for yaml files.
     *
     * @var array
     */
    public $sourcePaths;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->fileNamePatterns = [
          'test',
        ];
        $this->sourcePaths = [
            dirname(__FILE__).'/../../assets',
        ];
        $this->outputDir = '/tmp/merge-yaml';
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $expectedMergedFiles = $this->getExpectedMergedFiles();
        foreach ($expectedMergedFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

  /**
   * Tests for the MergeYaml class.
   */
    public function testMergeYaml()
    {
        $mergeYaml = new MergeYaml($this->fileNamePatterns, $this->sourcePaths, $this->outputDir);

        $mergeYaml->prepareOutputDir($this->outputDir);
        $this->assertDirectoryExists($this->outputDir);

        $ymlFilesPaths = $mergeYaml->getYamlFiles();
        $this->assertNotEmpty($ymlFilesPaths);
        $this->assertCount(1, $ymlFilesPaths);
        $this->assertArrayHasKey('test', $ymlFilesPaths);
        $this->assertCount(2, $ymlFilesPaths['test']);

        $mergedYmlContent = $mergeYaml->getMergedYmlContent($ymlFilesPaths['test']);
        $parsedMergedYmlContent = Yaml::parse($mergedYmlContent);
        $this->assertArrayHasKey('item_1', $parsedMergedYmlContent);
        $this->assertArrayHasKey('item_2', $parsedMergedYmlContent);

        $mergeYaml->createMergeFiles();

        $expectedMergedFiles = $this->getExpectedMergedFiles();
        foreach ($expectedMergedFiles as $expectedMergedFile) {
            $this->assertFileExists($expectedMergedFile);
            $mergedYmlFileContent = file_get_contents($expectedMergedFile);
            $this->assertNotEmpty($mergedYmlFileContent);
            $parsedMergedYmlFile = Yaml::parse($mergedYmlFileContent);
            $this->assertArrayHasKey('item_1', $parsedMergedYmlFile);
            $this->assertArrayHasKey('description', $parsedMergedYmlFile['item_1']);
            $this->assertArrayHasKey('value', $parsedMergedYmlFile['item_1']);
            $this->assertEquals('Item 1 description', $parsedMergedYmlFile['item_1']['description']);
            $this->assertEquals('Item 1 value', $parsedMergedYmlFile['item_1']['value']);
            $this->assertArrayHasKey('item_2', $parsedMergedYmlFile);
            $this->assertArrayHasKey('description', $parsedMergedYmlFile['item_2']);
            $this->assertArrayHasKey('value', $parsedMergedYmlFile['item_2']);
            $this->assertEquals('Item 2 description', $parsedMergedYmlFile['item_2']['description']);
            $this->assertEquals('Item 2 value', $parsedMergedYmlFile['item_2']['value']);
        }
    }

    /**
     * Gets the expected merged files.
     *
     * @return array
     *   The expected merged files.
     */
    public function getExpectedMergedFiles()
    {
        $expectedMergedFiles = [];
        foreach ($this->fileNamePatterns as $fileNamePattern) {
            $expectedMergedFiles[] = $this->outputDir.'/'.$fileNamePattern.'.merge.yml';
        }

        return $expectedMergedFiles;
    }
}
