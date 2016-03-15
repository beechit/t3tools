<?php
namespace BeechIt\T3tools\Command\Configuration;

    /*
     * This source file is proprietary property of Beech Applications B.V.
     * Date: 15-03-2016
     * All code (c) Beech Applications B.V. all rights reserved
     */
use BeechIt\T3tools\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CreateConfigCommand
 */
class CreateConfigCommand extends Command
{

    /**
     * Configure command
     */
    protected function configure()
    {
        $this
            ->setName('configuration:create')
            ->setDescription('Create configuration files');
    }

    public function run(InputInterface $input, OutputInterface $output) {

        if (!file_exists('servers.ini')) {
            copy(__DIR__ . '/../../../../resources/examples/servers.ini', 'servers.ini');
            $output->writeln('<info>Created servers.ini</info>');
        } else {
            $output->writeln('<comment>File servers.ini already exists</comment>');
        }

        if (!file_exists('rsync.conf')) {
            copy(__DIR__ . '/../../../../resources/examples/rsync.conf', 'rsync.conf');
            $output->writeln('<info>Created rsync.conf</info>');
        } else {
            $output->writeln('<comment>File rsync.conf already exists</comment>');
        }
    }

}