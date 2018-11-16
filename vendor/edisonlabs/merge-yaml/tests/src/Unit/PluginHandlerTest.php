<?php

namespace EdisonLabs\MergeYaml\Unit;

use Composer\Composer;
use EdisonLabs\MergeYaml\PluginHandler;
use PHPUnit\Framework\TestCase;

/**
 * Tests for EdisonLabs\MergeYaml\PluginHandler
 */
class PluginHandlerTest extends TestCase
{
    /**
     * A valid composer configuration for the plugin.
     *
     * @var array
     */
    protected $defaultConfig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $io;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->defaultConfig = [
            'files' => [
                'test',
            ],
            'locations' => [
                dirname(__FILE__).'/../../assets',
            ],
            'output-dir' => '/tmp/merge-yaml',
        ];

        $this->io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $file = '/tmp/merge-yaml/test.merge.yml';
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Tests setting up the plugin correctly.
     */
    public function testPlugin()
    {
        $mergeYaml = new PluginHandler(new Composer(), $this->io, $this->defaultConfig);
        $this->assertEquals($this->defaultConfig['files'], $mergeYaml->fileNamePatterns);
        $this->assertEquals($this->defaultConfig['locations'], $mergeYaml->sourcePaths);
        $this->assertEquals($this->defaultConfig['output-dir'], $mergeYaml->outputDir);
        $this->assertTrue($mergeYaml->isConfigured);

        $mergeYaml->createMergeFiles();
        $this->assertFileExists('/tmp/merge-yaml/test.merge.yml');
    }

    /**
     * Tests the plugin with missing files configuration.
     */
    public function testMissingFilesConfiguration()
    {
        $configParameters = $this->defaultConfig;
        unset($configParameters['files']);
        $this->expectException(\RuntimeException::class);
        $mergeYaml = new PluginHandler(new Composer(), $this->io, $configParameters);
    }

    /**
     * Tests the plugin with missing locations configuration.
     */
    public function testMissingLocationsConfiguration()
    {
        $configParameters = $this->defaultConfig;
        unset($configParameters['locations']);
        $this->expectException(\RuntimeException::class);
        $mergeYaml = new PluginHandler(new Composer(), $this->io, $configParameters);
    }

    /**
     * Tests the plugin with missing output-dir configuration.
     */
    public function testMissingOuptutDirConfiguration()
    {
        $configParameters = $this->defaultConfig;
        unset($configParameters['output-dir']);
        $this->expectException(\RuntimeException::class);
        $mergeYaml = new PluginHandler(new Composer(), $this->io, $configParameters);
    }
}
