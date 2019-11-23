<?php
/**
 *  ==================================================================
 *        文 件 名: Rollback.php
 *        概    要: 执行回滚
 *        作    者: IT小强
 *        创建时间: 2019-10-14 18:12
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace itxq\phinx\migrate;

use DateTime;
use itxq\phinx\Phinx;
use Phinx\Migration\AbstractMigration;
use Phinx\Migration\MigrationInterface;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

/**
 * 执行回滚
 * Class Rollback
 * @package itxq\phinx\migrate
 */
class Rollback extends Base
{
    /**
     * 设置指令
     */
    protected function configure(): void
    {
        $this->setName('migrate:rollback')
            ->setDescription('Rollback the last or to a specific migration')
            ->addOption('target', 't', Option::VALUE_REQUIRED, 'The version number to rollback to')
            ->addOption('date', 'd', Option::VALUE_REQUIRED, 'The date to rollback to')
            ->addOption('force', 'f', Option::VALUE_NONE, 'Force rollback to ignore breakpoints')
            ->addOption('package', 'p', Option::VALUE_REQUIRED, 'The package to migrate to')
            ->setHelp(<<<EOT
The <info>migrate:rollback</info> command reverts the last migration, or optionally up to a specific version

<info>php think migrate:rollback</info>
<info>php think migrate:rollback -t 20111018185412</info>
<info>php think migrate:rollback -d 20111018</info>
<info>php think migrate:rollback -v</info>
<info>php think migrate:rollback -p itxq/dna</info>

EOT
            );
    }

    /**
     * 执行指令
     * @param \think\console\Input  $input
     * @param \think\console\Output $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(Input $input, Output $output)
    {
        $packageName = (string)$input->getOption('package');
        if (empty($packageName)) {
            // 回滚本地
            if (is_dir($this->getLocalPhinxPath() . 'migrations')) {
                $this->rollback(Phinx::LOCAL, $input, $output);
            }
            // 回滚所有包
            foreach ($this->getVendorPackages() as $packageName => $packageInfo) {
                if (!isset($packageInfo['extra'][$this->extraName])) {
                    continue;
                }
                $this->rollback($packageName, $input, $output);
            }
        } else {
            // 回滚指定包，local表示本地
            $this->rollback($packageName, $input, $output);
        }
        $output->writeln('<info>Succeed!</info>');
    }

    /**
     * 执行回滚
     * @param string                $package
     * @param \think\console\Input  $input
     * @param \think\console\Output $output
     * @throws \Exception
     */
    protected function rollback(string $package, Input $input, Output $output): void
    {
        $version = $input->getOption('target');
        $date    = $input->getOption('date');
        $force   = (bool)$input->getOption('force');

        // rollback the specified environment
        $start = microtime(true);
        $output->writeln('>>> <info>' . $package . ': <comment>migrating!</comment></info>');
        if (null !== $date) {
            $this->rollbackToDateTime($package, $output, new DateTime($date), $force);
        } else {
            $this->rollbackToVersion($package, $output, $version, $force);
        }
        $end = microtime(true);

        $output->writeln('');
        $output->writeln('<comment>All Done. Took ' . sprintf('%.4fs', $end - $start) . '</comment>');
    }

    /**
     * 按版本回滚
     * @param string                $package
     * @param \think\console\Output $output
     * @param null                  $version
     * @param bool                  $force
     * @throws \think\Exception
     */
    protected function rollbackToVersion(string $package, Output $output, $version = null, $force = false): void
    {
        $migrations = $this->getMigrations($package);
        $versionLog = $this->getVersionLog($package);
        $versions   = array_keys($versionLog);

        ksort($migrations);
        sort($versions);


        // Check we have at least 1 migration to revert
        if (empty($versions) || $version === end($versions)) {
            $this->output->writeln('<error>No migrations to rollback</error>');
            return;
        }

        // If no target version was supplied, revert the last migration
        if (null === $version) {
            // Get the migration before the last run migration
            $prev    = count($versions) - 2;
            $version = $prev < 0 ? 0 : $versions[$prev];
        } else {
            // Get the first migration number
            $first = $versions[0];

            // If the target version is before the first migration, revert all migrations
            if ($version < $first) {
                $version = 0;
            }
        }

        // Check the target version exists
        if (0 !== $version && !isset($migrations[$version])) {
            $this->output->writeln("<error>Target version ($version) not found</error>");
            return;
        }

        // Revert the migration(s)
        krsort($migrations);
        foreach ($migrations as $migration) {
            /* @var AbstractMigration $migration */
            if ($migration->getVersion() <= $version) {
                break;
            }
            if (in_array($migration->getVersion(), $versions, false)) {
                if (!$force && isset($versionLog[$migration->getVersion()]) && 0 !== $versionLog[$migration->getVersion()]['breakpoint']) {
                    $this->output->writeln('<error>Breakpoint reached. Further rollbacks inhibited.</error>');
                    break;
                }
                $this->executeMigration($package, $output, $migration, MigrationInterface::DOWN);
            }
        }
    }

    /**
     * 按日期回滚
     * @param string                $package
     * @param \think\console\Output $output
     * @param \DateTime             $dateTime
     * @param bool                  $force
     * @throws \think\Exception
     */
    protected function rollbackToDateTime(string $package, Output $output, DateTime $dateTime, $force = false): void
    {
        $versions   = $this->getVersions($package);
        $dateString = $dateTime->format('YmdHis');
        sort($versions);

        $earlierVersion      = null;
        $availableMigrations = array_filter($versions, static function ($version) use ($dateString, &$earlierVersion) {
            if ($version <= $dateString) {
                $earlierVersion = $version;
            }
            return $version >= $dateString;
        });

        if (count($availableMigrations) > 0) {
            if ($earlierVersion === null) {
                $this->output->writeln('Rolling back all migrations');
                $migration = 0;
            } else {
                $this->output->writeln('Rolling back to version ' . $earlierVersion);
                $migration = $earlierVersion;
            }
            $this->rollbackToVersion($package, $output, $migration, $force);
        }
    }
}