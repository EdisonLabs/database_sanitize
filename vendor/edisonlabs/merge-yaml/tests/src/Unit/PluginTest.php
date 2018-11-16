<?php

namespace EdisonLabs\MergeYaml\Unit;

use EdisonLabs\MergeYaml\Plugin;
use Composer\Composer;
use Composer\Config;
use Composer\Script\ScriptEvents;
use PHPUnit\Framework\TestCase;

/**
 * Tests for EdisonLabs\MergeYaml\Plugin
 */
class PluginTest extends TestCase
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
    protected $packageMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventMock;

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

        $this->packageMock = $this->getMockBuilder('Composer\Package\RootPackage')
            ->disableOriginalConstructor()
            ->setMethods(['getExtra', 'setExtra'])
            ->getMock();
        $this->packageMock->expects($this->once())
            ->method('getExtra')
            ->will($this->returnValue(['merge-yaml' => $this->defaultConfig]));

        $this->eventMock = $this->getMockBuilder('Composer\Script\Event')
            ->disableOriginalConstructor()
            ->getMock();
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
     * Tests for EdisonLabs\MergeYaml\Plugin
     */
    public function testPlugin()
    {
        $plugin = new Plugin();

        $capabilities = $plugin->getCapabilities();
        $this->assertEquals(['Composer\Plugin\Capability\CommandProvider' => 'EdisonLabs\MergeYaml\CommandProvider'], $capabilities);

        $events = $plugin->getSubscribedEvents();
        $this->assertCount(2, $events);
        $this->assertArrayHasKey(ScriptEvents::POST_INSTALL_CMD, $events);
        $this->assertArrayHasKey(ScriptEvents::POST_UPDATE_CMD, $events);
        $this->assertEquals(['postCmd', -1], $events[ScriptEvents::POST_INSTALL_CMD]);
        $this->assertEquals(['postCmd', -1], $events[ScriptEvents::POST_UPDATE_CMD]);

        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();

        $composer = new Composer();
        $composer->setPackage($this->packageMock);
        $plugin->activate($composer, $io);
        $this->assertInstanceOf('\EdisonLabs\MergeYaml\PluginHandler', $plugin->getPluginHandler());

        $plugin->postCmd($this->eventMock);
        $this->assertFileExists('/tmp/merge-yaml/test.merge.yml');
    }
}
