#!/usr/bin/env php
<?php

if (PHP_SAPI !== 'cli') {
    echo 'Warning: T3tools may only be invoked from a command line', PHP_EOL;
}

require_once __DIR__ . '/../vendor/autoload.php';

use BeechIt\T3tools\Command;
use BeechIt\T3tools\Console\Application;
error_reporting(E_ALL);


$application = new Application('T3tools', '@package_version@');

// Load configuration
$application->loadConfiguration('servers.ini', 'servers');

$application->loadLocalTypo3Configuration(getenv('web_path') ?: './public_html/');

$application->add(new Command\DeployCommand());
$application->add(new Command\FetchCommand());
$application->add(new Command\Configuration\ShowCommand());
$application->add(new Command\Configuration\CreateConfigCommand());
//$application->add(new Command\SelfUpdateCommand());

$application->run();