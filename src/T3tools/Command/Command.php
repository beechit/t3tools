<?php
namespace BeechIt\T3tools\Command;

/*
 * This source file is proprietary property of Beech Applications B.V.
 * Date: 15-03-2016
 * All code (c) Beech Applications B.V. all rights reserved
 */
use BeechIt\T3tools\Service\SshConnection;
use BeechIt\T3tools\Service\SshService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class Command
 */
class Command extends \Symfony\Component\Console\Command\Command
{

    /**
     * Get selected server
     *
     * @param array $servers All availeble servers
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param $default server key or last|first
     * @return string|false
     */
    protected function selectServer(
        array $servers,
        InputInterface $input,
        OutputInterface $output,
        $default
    ) {

        if (empty($servers)) {
            $output->writeln('<error>No servers configured</error>');
            return false;
        }

        $helper = $this->getHelper('question');
        $server = $input->getArgument('server');
        if (empty($server)) {
            $serverOptions = array_keys($servers);
            switch ($default) {
                case 'first':
                    $default = $serverOptions[0];
                    break;
                case 'last':
                    $default = $serverOptions[count($serverOptions) - 1];
                    break;
            }

            $string = 'Please select server <comment>(' . implode(', ', $serverOptions) . ')</comment> ';
            $string .= '<info>[' . $default . ']</info>: ';
            $question = new Question(
                $string,
                $default
            );
            $question->setAutocompleterValues($serverOptions);
            $server = $helper->ask($input, $output, $question);
        }

        if (!isset($servers[$server])) {
            $output->writeln('<error>Unknown server ' . $server . '</error>');
            return false;
        }

        return $server;
    }

    /**
     * Let user select a value
     *
     * @param array $options Availeble options
     * @param string $defaultOption
     * @param string $question
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string|false
     */
    protected function userSelect(
        array $options,
        $defaultOption,
        $question,
        InputInterface $input,
        OutputInterface $output
    ) {

        if (empty($options)) {
            return $defaultOption;
        }

        $helper = $this->getHelper('question');
        $string = $question . ' <comment>(' . implode(', ', $options) . ')</comment> ';
        $string .= '<info>[' . $defaultOption . ']</info>: ';
        $question = new Question(
            $string,
            $defaultOption
        );
        $question->setAutocompleterValues($options);
        $value = $helper->ask($input, $output, $question);

        return $value;
    }

    /**
     * Ask user for input
     *
     * @param string $question
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $default Default value
     * @param bool $required
     * @return string
     */
    protected function askUserForInput($question, InputInterface $input, OutputInterface $output, $default = null, $required = false) {
        $helper = $this->getHelper('question');
        if ($default !== null) {
            $question .= ' <info>[' . $default . ']</info>';
        }
        $question = new Question($question .  ': ', $default);
        if ($required) {
            $question->setValidator(function ($value) {
                if (trim($value) == '') {
                    throw new \Exception('Value is required');
                }

                return $value;
            });
        }
        return $helper->ask($input, $output, $question);
    }

    /**
     * Get ssh connection
     *
     * @param array $serverConfiguration
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return SshConnection
     */
    protected function getSshConnection(array $serverConfiguration, InputInterface $input, OutputInterface $output) {

        $helper = $this->getHelper('question');
        // SSH connection
        $sshConnection = new SshConnection($serverConfiguration);
        while (!$sshConnection->testSsh()) {
            $question = new Question('Ssh pass <comment>(' . $sshConnection->getConfig('ssh_user') . '@' . $sshConnection->getConfig('ssh_host') . ')</comment>: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $question->setValidator(function ($value) {
                if (trim($value) == '') {
                    throw new \Exception('The password can not be empty');
                }

                return $value;
            });
            $sshConnection->setConf('ssh_pass', $helper->ask($input, $output, $question));
        }
        return $sshConnection;
    }

    /**
     * @return \mysqli
     */
    protected function getLocalTypo3DbConnection() {
        $localConfiguration = $this->getApplication()->getConfiguration('local_typo3');
        return new \mysqli(
            $localConfiguration['db']['host'],
            $localConfiguration['db']['username'],
            $localConfiguration['db']['password'],
            $localConfiguration['db']['database'],
            !empty($localConfiguration['db']['port']) ? $localConfiguration['db']['port'] : 3306,
            !empty($localConfiguration['db']['socket']) ? $localConfiguration['db']['socket'] : ''
        );
    }
}