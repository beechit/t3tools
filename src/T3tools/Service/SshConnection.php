<?php
namespace BeechIt\T3tools\Service;
/*
 * This source file is proprietary property of Beech Applications B.V.
 * Date: 15-03-2016
 * All code (c) Beech Applications B.V. all rights reserved
 */
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Ssh Connection
 */
class SshConnection
{
    /**
     * @var array
     */
    protected $serverConfiguration;

    /**
     * SshService constructor
     *
     * @param array $serverConfiguration
     */
    public function __construct(array $serverConfiguration)
    {
        $this->serverConfiguration = $serverConfiguration;
        $this->serverConfiguration['ssh_port'] = !empty($serverConfiguration['ssh_port']) ? $serverConfiguration['ssh_port'] : 22;
    }

    /**
     * Test SSH connection
     *
     * @return bool
     */
    public function testSsh() {
        // todo check ssh over shared ssh-key
        if (empty($this->serverConfiguration['ssh_pass'])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function setConf($key, $value) {
        $this->serverConfiguration[$key] = $value;
    }

    /**
     * Get config value
     *
     * @param string $key
     * @return string|null
     */
    public function getConfig($key) {
        $value = isset($this->serverConfiguration[$key]) ? $this->serverConfiguration[$key] : null;
        switch ($key) {
            case 'backups_path':
                if ($value === null) {
                    $value = rtrim($this->getConfig('web_root') ?: '', '/');
                    if ($value) {
                        $value .= '/';
                    }
                    $value .= 'backups/';
                }
                break;
        }
        return $value;
    }

    /**
     * @param string $parameters
     * @return int
     */
    public function rsync($parameters) {
        // Rsync code
        $command = [];
        if ($this->getConfig('ssh_pass')) {
            $command[] = 'sshpass -p' . $this->getConfig('ssh_pass');
        }
        $command[] = 'rsync -av';
        if ($this->getConfig('ssh_port')) {
            $command[] = '--rsh="ssh -p' . (int)$this->getConfig('ssh_port') . '"';
        }

        $command[] = $parameters;

        passthru(implode(' ', $command), $return);
        return $return;
    }

    /**
     * Exec typo3_console command on remote
     *
     * @param string $command
     * @param bool $passthru passthru or exec
     * @return int
     */
    public function typo3Console($command, $passthru = true, array &$output = []) {

        $realCommand = [];
        if ($this->getConfig('php_path')) {
            $realCommand[] = $this->getConfig('php_path');
        }
        if($this->getConfig('web_root')) {
            $realCommand[] = rtrim($this->getConfig('web_root'), '/') . '/typo3conf/ext/typo3_console/Scripts/typo3cms';
        } else {
            $realCommand[] = 'public_html/typo3conf/ext/typo3_console/Scripts/typo3cms';
        }
        $realCommand[] = $command;

        if ($passthru) {
            return $this->passthru(implode(' ', $realCommand));
        } else {
            return $this->exec(implode(' ', $realCommand), $output);
        }
    }

    /**
     * Exec command on remote server
     *
     * @param string $command
     * @param array $output
     * @return int
     */
    public function exec($command, array &$output = [])
    {
        $realCommand = [];
        if ($this->getConfig('ssh_pass')) {
            $realCommand[] = 'sshpass -p' . $this->getConfig('ssh_pass');
        }
        $realCommand[] = 'ssh';
        if ($this->getConfig('ssh_port')) {
            $realCommand[] = '-p' . (int)$this->getConfig('ssh_port');
        }

        $realCommand[] = $this->getConfig('ssh_user') . '@' . $this->getConfig('ssh_host');

        $realCommand[] = $command;

        exec(implode(' ', $realCommand), $output, $return);

        return $return;
    }

    /**
     * passthru command to remote server
     *
     * @param string $command
     * @return int
     */
    public function passthru($command)
    {
        $realCommand = [];
        if ($this->getConfig('ssh_pass')) {
            $realCommand[] = 'sshpass -p' . $this->getConfig('ssh_pass');
        }
        $realCommand[] = 'ssh';
        if ($this->getConfig('ssh_port')) {
            $realCommand[] = '-p' . (int)$this->getConfig('ssh_port');
        }

        $realCommand[] = $this->getConfig('ssh_user') . '@' . $this->getConfig('ssh_host');

        $realCommand[] = $command;

        passthru(implode(' ', $realCommand), $return);

        return $return;
    }
}
