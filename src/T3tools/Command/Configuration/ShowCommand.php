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

        // Servers
        foreach ($this->getApplication()->getConfiguration('servers') as $key => $config) {
            $output->writeln('<info>' . $key . '</info>');
            $table = new Table($output);
            foreach ($config as $label => $value) {
                $table->addRow([$label, $value]);
            }
            $table->render();
        }

    }

}
