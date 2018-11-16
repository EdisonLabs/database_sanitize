<?php

namespace EdisonLabs\MergeYaml;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MergeYamlCommand.
 */
class MergeYamlCommand extends BaseCommand
{

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output, array $configParameters = [])
    {
        $configFile = $input->getOption('config');
        if ($configFile && empty($configParameters)) {
            $filePath = realpath($configFile);

            // Checks if the file is valid.
            if (!$filePath || !$configfileContent = file_get_contents($filePath)) {
                throw new \RuntimeException("Unable to load the config file $configFile");
            }

            $configParameters = json_decode($configfileContent, true);
        }

        $mergeYaml = new PluginHandler($this->getComposer(), $this->getIO(), $configParameters);
        $mergeYaml->createMergeFiles();
    }

  /**
   * {@inheritdoc}
   */
    protected function configure()
    {
        parent::configure();
        $this->setName('merge-yaml')
            ->setDefinition($this->createDefinition())
            ->setDescription('Merge yaml files.');
    }

    /**
     * {@inheritdoc}
     */
    private function createDefinition()
    {
        return new InputDefinition(array(
            new InputOption('config', null, InputOption::VALUE_OPTIONAL, 'A json file containing the plugin configuration.'),
        ));
    }
}
