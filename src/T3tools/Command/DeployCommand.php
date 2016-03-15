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
        $servers = $this->getApplication()->getConfiguration('servers');
        if (empty($servers)) {
            $output->writeln('<error>No servers configured</error>');
            return 1;
        }

        if (!file_exists('rsync.conf')) {
            $output->writeln('<error>rsync.conf is missing</error>');
            return 1;
        }

        $helper = $this->getHelper('question');
        $server = $input->getArgument('server');
        if (empty($server)) {
            $serverOptions = array_keys($servers);
            $question = new Question('Please select server to deploy to <comment>(' . implode(', ', $serverOptions) . ')</comment> <info>[' . $serverOptions[0] . ']</info>: ', $serverOptions[0]);
            $question->setAutocompleterValues($serverOptions);
            $server = $helper->ask($input, $output, $question);
            $output->writeln('You have just selected: <info>' . $server . '</info>');
        }

        if (!isset($servers[$server])) {
            $output->writeln('<error>Unknown server ' . $server . '</error>');
            return 1;
        }

        // SSH connection
        $sshService = new SshService($servers[$server]);
        while (!$sshService->testSsh()) {
            $question = new Question('Ssh pass <comment>(' . $sshService->getConfig('ssh_user') . '@' . $sshService->getConfig('ssh_host') . ')</comment>: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $question->setValidator(function ($value) {
                if (trim($value) == '') {
                    throw new \Exception('The password can not be empty');
                }

                return $value;
            });
            $sshService->setConf('ssh_pass', $helper->ask($input, $output, $question));
        }

        $output->writeln('<info>rsync</info>');

        $command[] = '--include-from "rsync.conf"';
        $command[] = $this->getApplication()->getConfiguration('local_typo3')['web_root'] . ' ' . $sshService->getConfig('ssh_user') . '@' . $sshService->getConfig('ssh_host') . ':' . rtrim($sshService->getConfig('web_root'), '/') . '/';

        $return = $sshService->rsync(implode(' ', $command));

        if ((int)$return !== 0) {
            $output->writeln('<error>Rsync failed!!</error>');
            return 1;
        }

        $output->writeln('<info>Clear cache remote</info>');
        $return = $sshService->typo3Console('cache:flush');
        if ($return !== 0) {
            $output->writeln('<error>Flushing cache failed</error>');
            return 1;
        }

        // todo: clear autoloader info

        $output->writeln('<info>Preform database updates</info>');
        $return = $sshService->typo3Console('typo3_console:database:updateschema "*.add,*.change"');
        if ($return !== 0) {
            $output->writeln('<error>Database update failed</error>');
            return 1;
        }
    }
}
