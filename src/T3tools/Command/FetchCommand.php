<?php
namespace BeechIt\T3tools\Command;

    /*
     * This source file is proprietary property of Beech Applications B.V.
     * Date: 15-03-2016
     * All code (c) Beech Applications B.V. all rights reserved
     */
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class FetchCommand
 */
class FetchCommand extends Command
{
    /**
     * Configure command
     */
    protected function configure()
    {
        $this
            ->setName('fetch')
            ->setDescription('Fetch content from remote server')
            ->addArgument(
                'server',
                null,
                'Server to fetch content from'
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
        $backupPath = $this->getApplication()->getConfiguration('local_typo3')['backups_path'];
        if (!file_exists($backupPath)) {
            $output->writeln('<error>Backup folder not found. Please create folder "' . $backupPath . '"</error>');
            return 1;
        }

        // Server
        $servers = $this->getApplication()->getConfiguration('servers');
        $server = $this->selectServer($servers, $input, $output, 'last');
        // Exit when no server found
        if ($server === false) {
            return 1;
        }

        // SSH connection
        $sshConnection = $this->getSshConnection($servers[$server], $input, $output);

        $backupName = $server . '-' . date('YmdHis');
        $output->writeln('<info>Creating remote backup ' . $backupName . '</info>');
        $return = $sshConnection->typo3Console('backup:create ' . $backupName);
        if ($return !== 0) {
            $output->writeln('<error>Remote backup failed</error>');
            return 1;
        }

        $output->writeln('<info>rsync ' . $backupName . '</info>');
        $command = [];
        $fileNameRemoteBackup = rtrim($sshConnection->getConfig('backups_path'), '/') . '/' . $backupName . '-backup.tgz';
        $fileNameLocalBackup = $backupPath . $backupName . '-backup.tgz';
        $command[] = $sshConnection->getConfig('ssh_user') . '@' . $sshConnection->getConfig('ssh_host') . ':' . $fileNameRemoteBackup;
        $command[] = $fileNameLocalBackup;

        $return = $sshConnection->rsync(implode(' ', $command));

        if ((int)$return !== 0) {
            $output->writeln('<error>Rsync failed!!</error>');
            return 1;
        }

        $output->writeln('<info>Delete remote backup</info>');
        $sshConnection->passthru('rm ' . $fileNameRemoteBackup);

        $db = $this->getLocalTypo3DbConnection();

        foreach (['sys_domain', 'be_users'] as $table) {
            $db->query('CREATE TABLE ' . $table . '_local LIKE ' . $table);
            $db->query('INSERT INTO ' . $table . '_local SELECT * FROM ' . $table);
        }

        $output->writeln('<info>Restore backup local</info>');
        $command = [];
        $command[] = $this->getApplication()->getConfiguration('local_typo3.php_path') ?: 'php';
        $command[] = $this->getApplication()->getConfiguration('local_typo3.project_path') . 'typo3cms backup:restore ' . $backupName;
        passthru(implode(' ', $command), $return);

        if ($return !== 0) {
            $output->writeln('<error>Restore failed!!</error>');
            return 1;
        }

        if ($db->query('SHOW TABLES like "fe_users"')) {
            $db->query('UPDATE fe_users SET username = MD5(username), password = MD5(password), email = CONCAT(LEFT(UUID(), 8), "@beech.it") WHERE email NOT LIKE "%@beech.it"');
        }

        // Keep domain info of local environment
        $db->query('
            UPDATE
                sys_domain AS a
            JOIN
                sys_domain_local AS b
            ON
                a.uid = b.uid
            SET
                a.domainName = b.domainName,
                a.redirectTo = b.redirectTo
        ');

        if ($db->error) {
            $output->writeln('<error>' . $db->error . '</error>');
        }

        // Disable all BE users
        $db->query('UPDATE be_users SET disable = 1');

        // Keep BE users info of local environment
        $db->query('
            UPDATE
                be_users AS a
            JOIN
                be_users_local AS b
            ON
                a.uid = b.uid
            SET
                a.username = b.username,
                a.password = b.password,
                a.admin = b.admin,
                a.disable = b.disable,
                a.deleted = b.deleted
        ');
        if ($db->error) {
            $output->writeln('<error>' . $db->error . '</error>');
        }

        foreach (['sys_domain', 'be_users'] as $table) {
            $db->query('DROP TABLE  ' . $table . '_local');
        }

        // Cleanup backup file
        unlink($fileNameLocalBackup);

        $output->writeln('<comment>Import of remote data done</comment>');
    }
}
