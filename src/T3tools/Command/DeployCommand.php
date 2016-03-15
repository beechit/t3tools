<?php
namespace BeechIt\T3tools\Command;

/*
 * This source file is proprietary property of Beech Applications B.V.
 * Date: 15-03-2016
 * All code (c) Beech Applications B.V. all rights reserved
 */
use BeechIt\T3tools\Service\SshService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class DeployCommand
 */
class DeployCommand extends Command
{
    /**
     * Configure command
     */
    protected function configure()
    {
        $this
            ->setName('deploy')
            ->setDescription('Deploy current build')
            ->addArgument(
                'server',
                null,
                'Server to deploy to'
            );
    }

    /**
     * Execute command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void|int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        // Server
        // Server
        $servers = $this->getApplication()->getConfiguration('servers');
        $server = $this->selectServer($servers, $input, $output, 'first');
        // Exit when no server found
        if ($server === false) {
            return 1;
        }

        if (!file_exists('rsync.conf')) {
            $output->writeln('<error>rsync.conf is missing</error>');
            return 1;
        }

        $sshConnection = $this->getSshConnection($servers[$server], $input, $output);

        $output->writeln('<info>rsync</info>');

        $command[] = '--include-from "rsync.conf"';
        $command[] = $this->getApplication()->getConfiguration('local_typo3')['web_root'] . ' ' . $sshConnection->getConfig('ssh_user') . '@' . $sshConnection->getConfig('ssh_host') . ':' . rtrim($sshConnection->getConfig('web_root'), '/') . '/';

        $return = $sshConnection->rsync(implode(' ', $command));

        if ((int)$return !== 0) {
            $output->writeln('<error>Rsync failed!!</error>');
            return 1;
        }

        $output->writeln('<info>Clear cache remote</info>');
        $return = $sshConnection->typo3Console('cache:flush');
        if ($return !== 0) {
            $output->writeln('<error>Flushing cache failed</error>');
            return 1;
        }

        // todo: clear autoloader info

        $output->writeln('<info>Preform database updates</info>');
        $return = $sshConnection->typo3Console('typo3_console:database:updateschema "*.add,*.change"');
        if ($return !== 0) {
            $output->writeln('<error>Database update failed</error>');
            return 1;
        }
    }
}
