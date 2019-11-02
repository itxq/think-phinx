<?php
/**
 *  ==================================================================
 *        文 件 名: Run.php
 *        概    要: 执行迁移
 *        作    者: IT小强
 *        创建时间: 2019-10-12 15:30
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace itxq\phinx\migrate;

use DateTime;
use Phinx\Migration\AbstractMigration;
use Phinx\Migration\MigrationInterface;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

/**
 * 执行迁移
 * Class Run
 * @package itxq\phinx\migrate
 */
class Run extends Base
{
    /**
     * 设置指令
     */
    protected function configure(): void
    {
        $this->setName('migrate:run')
            ->setDescription('Migrate the database')
            ->addOption('target', 't', Option::VALUE_REQUIRED, 'The version number to migrate to')
            ->addOption('date', 'd', Option::VALUE_REQUIRED, 'The date to migrate to')
            ->addOption('package', 'p', Option::VALUE_REQUIRED, 'The package to migrate to')
            ->setHelp(<<<EOT
The <info>dna:migrate</info> command runs all available migrations, optionally up to a specific version

<info>php think migrate:run</info>
<info>php think migrate:run -t 20110103081132</info>
<info>php think migrate:run -d 20110103</info>
<info>php think migrate:run -v</info>
<info>php think migrate:run -p itxq/dna</info>

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
            // 迁移本地
            if (is_dir($this->getLocalPhinxPath() . 'migrations')) {
                $this->migrate('local', $input, $output);
            }
            // 迁移所有包
            foreach ($this->getVendorPackages() as $packageName => $packageInfo) {
                if (!isset($packageInfo['extra'][$this->extraName])) {
                    continue;
                }
                $this->migrate($packageName, $input, $output);
            }
        } else {
            // 迁移指定包，local表示本地
            $this->migrate($packageName, $input, $output);
        }
        $output->writeln('<info>Succeed!</info>');
    }

    /**
     * 执行迁移
     * @param string                $package
     * @param \think\console\Input  $input
     * @param \think\console\Output $output
     * @throws \Exception
     */
    protected function migrate(string $package, Input $input, Output $output): void
    {
        $version = $input->getOption('target');
        $date    = $input->getOption('date');
        $output->writeln('>>> <info>' . $package . ': <comment>migrating!</comment></info>');
        $start = microtime(true);
        if (null !== $date) {
            $this->migrateToDateTime($package, $output, new DateTime($date));
        } else {
            $this->migrateToVersion($package, $output, $version);
        }
        $end = microtime(true);
        $output->writeln('');
        $output->writeln('<comment>All Done. Took ' . sprintf('%.4fs', $end - $start) . '</comment>');
    }

    /**
     * 按日期迁移
     * @param string                $package
     * @param \think\console\Output $output
     * @param \DateTime             $dateTime
     * @throws \think\Exception
     */
    protected function migrateToDateTime(string $package, Output $output, DateTime $dateTime): void
    {
        $versions              = array_keys($this->getMigrations($package));
        $dateString            = $dateTime->format('YmdHis');
        $outstandingMigrations = array_filter($versions, static function ($version) use ($dateString) {
            return $version <= $dateString;
        });
        if (count($outstandingMigrations) > 0) {
            $migration = max($outstandingMigrations);
            $output->writeln('Migrating to version ' . $migration);
            $this->migrateToVersion($package, $output, $migration);
        }
    }

    /**
     * 迁移到指定版本
     * @param string                $package
     * @param \think\console\Output $output
     * @param null                  $version
     * @throws \think\Exception
     */
    protected function migrateToVersion(string $package, Output $output, $version = null): void
    {
        $migrations = $this->getMigrations($package);
        $versions   = $this->getVersions($package);
        $current    = $this->getCurrentVersion($package);
        if (empty($versions) && empty($migrations)) {
            return;
        }
        if (null === $version) {
            $version = max(array_merge($versions, array_keys($migrations)));
        } else if (0 !== $version && !isset($migrations[$version])) {
            $output->writeln(sprintf('<comment>warning</comment> %s is not a valid version', $version));
            return;
        }

        // are we migrating up or down?
        $direction = $version > $current ? MigrationInterface::UP : MigrationInterface::DOWN;
        if ($direction === MigrationInterface::DOWN) {
            // run downs first
            krsort($migrations);
            foreach ($migrations as $migration) {
                /* @var AbstractMigration $migration */
                if ($migration->getVersion() <= $version) {
                    break;
                }

                if (in_array($migration->getVersion(), $versions, false)) {
                    $this->executeMigration($package, $output, $migration, MigrationInterface::DOWN);
                }
            }
        } else {
            ksort($migrations);
            foreach ($migrations as $migration) {
                /* @var AbstractMigration $migration */
                if ($migration->getVersion() > $version) {
                    break;
                }
                if (!in_array($migration->getVersion(), $versions, false)) {
                    $this->executeMigration($package, $output, $migration, MigrationInterface::UP);
                }
            }
        }
    }

    /**
     * 获取当前版本
     * @param string $package
     * @return int|mixed
     */
    protected function getCurrentVersion(string $package)
    {
        $versions = $this->getVersions($package);
        $version  = 0;
        if (!empty($versions)) {
            $version = end($versions);
        }
        return $version;
    }
}