<?php
namespace BeechIt\T3tools\Command\Configuration;

/*
 * This source file is proprietary property of Beech Applications B.V.
 * Date: 15-03-2016
 * All code (c) Beech Applications B.V. all rights reserved
 */
use BeechIt\T3tools\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ShowCommand
 */
class ShowCommand extends Command
{
    /**
     * Configure command
     */
    protected function configure()
    {
        $this
            ->setName('configuration:show')
            ->setDescription('Show current configuration');
    }

    /**
     * Execute command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->getApplication()->getConfiguration('servers')) {
            $output->writeln('<error>No server configuration found (servers.ini or servers.json)</error>');
        } else {
            // Servers
            foreach ($this->getApplication()->getConfiguration('servers') as $key => $config) {
                $output->writeln('<info>server.' . $key . '</info>');
                $table = new Table($output);
                foreach ($config as $label => $value) {
                    $table->addRow([$label, $value]);
                }
                $table->render();
            }
        }
        $output->writeln('');

        if (!$this->getApplication()->getConfiguration('local_typo3')) {
            $output->writeln('<error>No local typo3 installation found</error>');
        } else {
            $output->writeln('<info>local_typo3</info>');
            $table = new Table($output);
            foreach ($this->getApplication()->getConfiguration('local_typo3') as $key => $config) {
                if (is_array($config)) {
                    foreach ($config as $label => $value) {
                        $table->addRow([$key . '.' . $label, $value]);
                    }
                } else {
                    $table->addRow([$key, $config]);
                }
            }
            $table->render();
        }

        $output->writeln('');
    }
}
