<?php

namespace EdisonLabs\MergeYaml;

use Composer\Composer;
use Composer\IO\IOInterface;

/**
 * Class PluginHandler.
 */
class PluginHandler
{

  /**
   * IO object.
   *
   * @var \Composer\IO\IOInterface
   */
    protected $io;

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
    public $sourcePaths = array();

    /**
     * Flag indicating whether the plugin has configuration or not.
     *
     * @var bool
     */
    public $isConfigured = false;

    /**
     * {@inheritdoc}
     *
     * @param \Composer\Composer $composer
     *   The Composer object.
     * @param \Composer\IO\IOInterface $io
     *   The IO object.
     * @param array $configParameters
     *   The config parameters to override the extra config from composer.json.
     */
    public function __construct(Composer $composer, IOInterface $io, array $configParameters = array())
    {
        $this->io = $io;

        $config = $configParameters;
        if (!$config) {
            $extra = $composer->getPackage()->getExtra();

            if (!isset($extra['merge-yaml'])) {
                return;
            }

            $config = $extra['merge-yaml'];
        }

        // Get files.
        if (empty($config['files'])) {
            throw new \RuntimeException('Please configure merge-yaml files in your composer.json');
        }
        $this->fileNamePatterns = $config['files'];

        // Get locations.
        if (empty($config['locations']) || !is_array($config['locations'])) {
            throw new \RuntimeException('Please configure merge-yaml locations in your composer.json');
        }

        $this->sourcePaths = $config['locations'];

        // Get output dir.
        if (empty($config['output-dir'])) {
            throw new \RuntimeException('Please configure merge-yaml output-dir in your composer.json');
        }
        $this->outputDir = $config['output-dir'];

        $this->isConfigured = true;
    }

    /**
     * Creates the merge files.
     */
    public function createMergeFiles()
    {
        if (!$this->isConfigured) {
            $this->io->write('> WARNING: merge-yaml is not configured', true);

            return;
        }

        $mergeYaml = new MergeYaml($this->fileNamePatterns, $this->sourcePaths, $this->outputDir);

        $processedFiles = $mergeYaml->createMergeFiles();

        if (empty($processedFiles)) {
            $this->io->write('> merge-yaml: No merge files have been created', true);

            return;
        }

        foreach ($processedFiles as $fileName => $filePaths) {
            foreach ($filePaths as $filePath) {
                $this->io->write("> merge-yaml: Merging $filePath", true);
            }

            $outputDir = $this->outputDir;
            $this->io->write("> merge-yaml: Merged in $outputDir/$fileName.merge.yml", true);
        }
    }
}
