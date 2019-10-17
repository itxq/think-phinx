<?php
/**
 *  ==================================================================
 *        文 件 名: Base.php
 *        概    要: migrate 命令行基类
 *        作    者: IT小强
 *        创建时间: 2019-10-12 16:31
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace itxq\phinx\migrate;

use DateTime;
use DateTimeZone;
use http\Exception\InvalidArgumentException;
use itxq\phinx\Phinx;
use Phinx\Db\Adapter\AdapterFactory;
use Phinx\Db\Adapter\ProxyAdapter;
use Phinx\Migration\AbstractMigration;
use Phinx\Migration\MigrationInterface;
use think\console\Output;

/**
 * migrate 命令行基类
 * Class Base
 * @package itxq\phinx\migrate
 */
abstract class Base extends Phinx
{
    /**
     * @var array 迁移实例列表
     */
    protected $migrations = [];

    /**
     * 执行迁移
     * @param string                              $package
     * @param \think\console\Output               $output
     * @param \Phinx\Migration\MigrationInterface $migration
     * @param                                     $direction
     */
    protected function executeMigration(string $package, Output $output, MigrationInterface $migration, $direction): void
    {
        $output->writeln('');
        $output->writeln(' ==' . ' <info>' . $migration->getVersion() . ' ' . $migration->getName() . ':</info>' . ' <comment>' . (MigrationInterface::UP === $direction ? 'migrating' : 'reverting') . '</comment>');

        // Execute the migration and log the time elapsed.
        $start = microtime(true);

        $startTime = time();
        $direction = (MigrationInterface::UP === $direction) ? MigrationInterface::UP : MigrationInterface::DOWN;
        $migration->setAdapter($this->getAdapter($package));

        // begin the transaction if the adapter supports it
        if ($this->getAdapter($package)->hasTransactions()) {
            $this->getAdapter($package)->beginTransaction();
        }

        // Run the migration
        if (method_exists($migration, MigrationInterface::CHANGE)) {
            if (MigrationInterface::DOWN === $direction) {
                // Create an instance of the ProxyAdapter so we can record all
                // of the migration commands for reverse playback
                /** @var ProxyAdapter $proxyAdapter */
                $proxyAdapter = AdapterFactory::instance()->getWrapper('proxy', $this->getAdapter($package));
                $migration->setAdapter($proxyAdapter);
                /** @noinspection PhpUndefinedMethodInspection */
                $migration->change();
                $proxyAdapter->executeInvertedCommands();
                $migration->setAdapter($this->getAdapter($package));
            } else {
                /** @noinspection PhpUndefinedMethodInspection */
                $migration->change();
            }
        } else {
            $migration->{$direction}();
        }

        // commit the transaction if the adapter supports it
        if ($this->getAdapter($package)->hasTransactions()) {
            $this->getAdapter($package)->commitTransaction();
        }

        // Record it in the database
        $this->getAdapter($package)->migrated($migration, $direction, date('Y-m-d H:i:s', $startTime),
            date('Y-m-d H:i:s'));

        $end = microtime(true);

        $output->writeln(' ==' . ' <info>' . $migration->getVersion() . ' ' . $migration->getName() . ':</info>' . ' <comment>' . (MigrationInterface::UP === $direction ? 'migrated' : 'reverted') . ' ' . sprintf('%.4fs',
                $end - $start) . '</comment>');
    }

    /**
     * 获取迁移实例列表
     * @param string $package
     * @return array
     */
    protected function getMigrations(string $package): array
    {
        if (isset($this->migrations[$package])) {
            return $this->migrations[$package];
        }
        $path     = $this->getPhinxPaths($package);
        $phpFiles = $this->getPhpFile($path['migrations']);

        // filter the files to only get the ones that match our naming scheme
        $fileNames = [];

        $versions = [];

        foreach ($phpFiles as $filePath) {
            $classInfo = $this->getClassInfoFromFileName($filePath);
            if ($classInfo === null) {
                continue;
            }
            $class   = $classInfo['class'];
            $version = $classInfo['version'];

            if (isset($versions[$version])) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Duplicate migration - "%s" has the same version as "%s"',
                        $filePath,
                        $versions[$version]->getVersion()
                    )
                );
            }

            if (isset($fileNames[$class])) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Migration "%s" has the same name as "%s"',
                        basename($filePath),
                        $fileNames[$class]
                    )
                );
            }

            $fileNames[$class] = basename($filePath);

            // load the migration file
            /** @noinspection PhpIncludeInspection */
            require_once $filePath;
            if (!class_exists($class)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Could not find class "%s" in file "%s"',
                        $class,
                        $filePath
                    )
                );
            }
            // instantiate it
            $migration = new $class(null, $version);
            if (!($migration instanceof AbstractMigration)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'The class "%s" in file "%s" must extend \Phinx\Migration\AbstractMigration',
                        $class,
                        $filePath
                    )
                );
            }
            $versions[$version] = $migration;

        }

        ksort($versions);

        return $versions;
    }

    /**
     * 获取版本信息
     * @param string $package
     * @return array
     */
    protected function getVersions(string $package): array
    {
        $adapter = $this->getAdapter($package);
        return $adapter->getVersions();
    }

    /**
     * 获取版本信息
     * @param string $package
     * @return array
     */
    protected function getVersionLog(string $package): array
    {
        return $this->getAdapter($package)->getVersionLog();
    }

    /**
     * 获取迁移类信息
     * @param $fileName
     * @return array|null
     */
    protected function getClassInfoFromFileName($fileName): array
    {
        $info = null;
        if (preg_match('/^(Db(\d+).*?)\.php$/', basename($fileName), $matches)) {
            $info = [
                'file'    => $matches[0],
                'class'   => $matches[1],
                'version' => $matches[2],
            ];
        } else if (preg_match('/^(\d+)_(.*?)\.php$/', basename($fileName), $matches)) {
            $info = [
                'file'    => $matches[0],
                'class'   => parse_name($matches[2], 1),
                'version' => $matches[1],
            ];
        }
        return $info;
    }

    /**
     * 生成指定格式的文件名
     * @param $className
     * @return string
     * @throws \Exception
     */
    protected function mapClassNameToFileName($className): string
    {

        return 'Db' . $this->getCurrentTimestamp() . parse_name($className, 1);
    }

    /**
     * 获取当前时间字符串 UTC
     * @return string
     * @throws \Exception
     */
    protected function getCurrentTimestamp(): string
    {
        $dt = new DateTime('now', new DateTimeZone('UTC'));

        return $dt->format('YmdHis');
    }
}