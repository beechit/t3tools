<?php
namespace BeechIt\T3tools\Console;

/*
 * This source file is proprietary property of Beech Applications B.V.
 * Date: 15-03-2016
 * All code (c) Beech Applications B.V. all rights reserved
 */
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class Application
 */
class Application extends \Symfony\Component\Console\Application
{
    /**
     * @var array
     */
    protected $configurationFiles = [];

    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * Gets the default input definition.
     *
     * @return InputDefinition An InputDefinition instance
     */
    protected function getDefaultInputDefinition()
    {
        return new InputDefinition(array(
            new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),

            new InputOption('--help', '-h', InputOption::VALUE_NONE, 'Display this help message'),
            new InputOption('--version', '-V', InputOption::VALUE_NONE, 'Display this application version'),
        ));
    }

    /**
     * @param string $file
     * @param string $key
     * @param string $type
     * @return void
     */
    public function loadConfiguration($file, $key = null, $type = 'ini') {

        // Check if config file exists
        if (!file_exists($file)) {
            return;
        }
        $config = [];
        switch ($type) {
            case 'ini':
                $config = parse_ini_file($file, true);
                break;

            case 'json':
                $config = json_decode(file_get_contents($file), true);
                break;

            default;
                throw new RuntimeException('Unknown configuration `type`');
        }

        $this->configurationFiles[] = $file;
        if ($config) {
            if ($key) {
                if (!isset($this->configuration[$key])) {
                    $this->configuration[$key] = [];
                }
                $this->configuration[$key] = array_merge($this->configuration[$key], $config);
            } else {
                $this->configuration = array_merge($this->configuration, $config);
            }
        }
    }

    /**
     * @param string $webRootPath
     * @return void
     */
    public function loadLocalTypo3Configuration($webRootPath) {
        $webRootPath = rtrim($webRootPath, '/') . '/';
        if (!file_exists($webRootPath . 'typo3conf/LocalConfiguration.php')) {
            return;
        }

        $GLOBALS['TYPO3_CONF_VARS'] = include($webRootPath . 'typo3conf/LocalConfiguration.php');
        if (@file_exists($webRootPath . 'typo3conf/AdditionalConfiguration.php')) {
            require $webRootPath . 'typo3conf/AdditionalConfiguration.php';
        }

        $this->configuration['local_typo3'] = [
            'db' => [
                'host' => $GLOBALS['TYPO3_CONF_VARS']['DB']['host'],
                'username' => $GLOBALS['TYPO3_CONF_VARS']['DB']['username'],
                'password' => $GLOBALS['TYPO3_CONF_VARS']['DB']['password'],
                'database' => $GLOBALS['TYPO3_CONF_VARS']['DB']['database'],
                'port' => !empty($GLOBALS['TYPO3_CONF_VARS']['DB']['port']) ? $GLOBALS['TYPO3_CONF_VARS']['DB']['port'] : 3306,
                'socket' => !empty($GLOBALS['TYPO3_CONF_VARS']['DB']['socket']) ? $GLOBALS['TYPO3_CONF_VARS']['DB']['socket'] : '',
            ],
            'web_root' => $webRootPath,
            'backups_path' => $webRootPath . 'backups/',
            'php_path' => PHP_BINARY,
        ];
    }

    /**
     * Get configuration
     *
     * @param string $key
     * @return mixed
     */
    public function getConfiguration($key = null) {
        if ($key) {
            $value = $this->configuration;
            foreach (explode('.', $key) as $partKey) {
                if (!array_key_exists($partKey, $value)) {
                    return null;
                }
                $value = $value[$partKey];
            }
            return $value;
        } else {
            return $this->configuration;
        }
    }
}