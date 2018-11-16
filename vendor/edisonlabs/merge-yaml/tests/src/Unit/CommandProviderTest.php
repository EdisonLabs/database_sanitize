<?php

namespace EdisonLabs\MergeYaml\Unit;

use EdisonLabs\MergeYaml\CommandProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for EdisonLabs\MergeYaml\CommandProvider
 */
class CommandProviderTest extends TestCase
{
    /**
     * Tests for EdisonLabs\MergeYaml\CommandProvider
     */
    public function testCommandProvider()
    {
        $commandProvider = new CommandProvider();
        $commands = $commandProvider->getCommands();
        $this->assertCount(1, $commands);
        $this->assertInstanceOf('EdisonLabs\MergeYaml\MergeYamlCommand', $commands[0]);
    }
}
