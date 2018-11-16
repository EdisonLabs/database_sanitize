<?php

namespace EdisonLabs\MergeYaml\Unit;

use Composer\Composer;
use EdisonLabs\MergeYaml\MergeYamlCommand;
use PHPUnit\Framework\TestCase;

/**
 * Tests for EdisonLabs\MergeYaml\MergeYamlCommand
 */
class MergeYamlCommandTest extends TestCase
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
    protected $inputMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $outputMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $packageMock;

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

        $this->inputMock = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')->getMock();
        $this->outputMock = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')->getMock();
        $this->packageMock = $this->getMockBuilder('Composer\Package\RootPackageInterface')->getMock();
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
    public function testMergeYamlCommand()
    {
        $mergeYamlCommand = new MergeYamlCommand();
        $this->assertEquals('merge-yaml', $mergeYamlCommand->getName());
        $this->assertEquals('Merge yaml files.', $mergeYamlCommand->getDescription());

        $composer = new Composer();
        $composer->setPackage($this->packageMock);
        $mergeYamlCommand->setComposer($composer);
        $mergeYamlCommand->execute($this->inputMock, $this->outputMock, $this->defaultConfig);
        $this->assertFileExists('/tmp/merge-yaml/test.merge.yml');
    }
}
