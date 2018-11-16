<?php

namespace EdisonLabs\MergeYaml;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

/**
 * Composer plugin to merge yaml files.
 */
class Plugin implements PluginInterface, EventSubscriberInterface, Capable
{

  /**
   * The Plugin handler object.
   *
   * @var \EdisonLabs\MergeYaml\PluginHandler
   */
    protected $pluginHandler;

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->pluginHandler = new PluginHandler($composer, $io);
    }

    /**
     * {@inheritdoc}
     */
    public function getCapabilities()
    {
        return array(
            'Composer\Plugin\Capability\CommandProvider' => 'EdisonLabs\MergeYaml\CommandProvider',
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            ScriptEvents::POST_INSTALL_CMD => array('postCmd', -1),
            ScriptEvents::POST_UPDATE_CMD => array('postCmd', -1),
        );
    }

    /**
     * Post command event callback.
     *
     * @param \Composer\Script\Event $event
     *   Event object.
     */
    public function postCmd(Event $event)
    {
        $this->pluginHandler->createMergeFiles();
    }

    /**
     * Gets the plugin handler.
     *
     * @return \EdisonLabs\MergeYaml\PluginHandler
     *   The MergeYaml plugin handler.
     */
    public function getPluginHandler()
    {
        return $this->pluginHandler;
    }
}
