<?php

namespace EdisonLabs\MergeYaml;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

/**
 * Class CommandProvider.
 */
class CommandProvider implements CommandProviderCapability
{

  /**
   * {@inheritdoc}
   */
    public function getCommands()
    {
        return array(
            new MergeYamlCommand(),
        );
    }
}
