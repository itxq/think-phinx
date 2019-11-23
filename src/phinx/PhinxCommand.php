<?php
/**
 *  ==================================================================
 *        文 件 名: PhinxCommand.php
 *        概    要: Phinx 命令行基类
 *        作    者: IT小强
 *        创建时间: 2019-10-12 15:06
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace itxq\phinx;

use Phinx\Db\Adapter\AdapterFactory;
use Phinx\Db\Adapter\AdapterInterface;
use think\Exception;

/**
 * Phinx 命令行基类
 * Class Phinx
 * @package itxq\phinx
 */
abstract class PhinxCommand extends Command
{
    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var string
     */
    protected $extraName = 'itxq-phinx';

    /**
     * Get an adapter
     * @param string $package 包名称
     * @return \Phinx\Db\Adapter\AdapterInterface
     */
    protected function getAdapter(string $package): AdapterInterface
    {
        if (isset($this->adapter[$package])) {
            return $this->adapter[$package];
        }
        $options = $this->getPhinxConfig($package);

        $adapter = AdapterFactory::instance()->getAdapter($options['adapter'], $options);
        if ($adapter->hasOption('table_prefix') || $adapter->hasOption('table_suffix')) {
            $adapter = AdapterFactory::instance()->getWrapper('prefix', $adapter);
        }
        $this->adapter[$package] = $adapter;
        return $this->adapter[$package];
    }

    /**
     * 获取配置
     * @param string $package
     * @return array
     */
    protected function getPhinxConfig(string $package): array
    {
        $default = $this->app->config->get('database.default');

        $config = $this->app->config->get("database.connections.{$default}");

        if (0 === (int)$config['deploy']) {
            $phinxConfig = [
                'adapter'      => $config['type'],
                'host'         => $config['hostname'],
                'name'         => $config['database'],
                'user'         => $config['username'],
                'pass'         => $config['password'],
                'port'         => $config['hostport'],
                'charset'      => $config['charset'],
                'table_prefix' => $config['prefix'],
            ];
        } else {
            $phinxConfig = [
                'adapter'      => explode(',', $config['type'])[0],
                'host'         => explode(',', $config['hostname'])[0],
                'name'         => explode(',', $config['database'])[0],
                'user'         => explode(',', $config['username'])[0],
                'pass'         => explode(',', $config['password'])[0],
                'port'         => explode(',', $config['hostport'])[0],
                'charset'      => explode(',', $config['charset'])[0],
                'table_prefix' => explode(',', $config['prefix'])[0],
            ];
        }

        $table = $this->app->config->get('phinx.migration_table', 'migration');
        if (!empty($package)) {
            $table .= '_' . strtolower(md5($package));
        }

        $defaultPhinxPath                       = realpath($this->app->getRootPath()) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR;
        $phinxConfig['phinx_path']              = $this->app->config->get('phinx.phinx_path', $defaultPhinxPath);
        $phinxConfig['default_migration_table'] = $phinxConfig['table_prefix'] . $table;
        $phinxConfig['version_order']           = $this->app->config->get('phinx.version_order', 'creation');
        return $phinxConfig;
    }

    /**
     * 获取目录下所有的PHP 文件
     * @param array $path
     * @return array
     */
    protected function getPhpFile(array $path): array
    {
        $phpFiles = [];
        foreach ($path as $v) {
            $phpFiles[] = glob($v . '*.php', defined('GLOB_BRACE') ? GLOB_BRACE : 0);
        }
        if (count($phpFiles) >= 1) {
            $phpFiles = array_merge(...$phpFiles);
        }
        return $phpFiles;
    }

    /**
     * 获取phinx路径
     * @param string packageName 包名称
     * @return array
     */
    protected function getPhinxPaths(string $packageName): array
    {
        $di          = DIRECTORY_SEPARATOR;
        $phinxConfig = ['migrations' => [], 'seeds' => []];
        if (empty($packageName)) {
            return $phinxConfig;
        }
        if ($packageName === 'local') {
            $phinxPath      = $this->getLocalPhinxPath();
            $migrationsPath = $phinxPath . 'migrations' . $di;
            $seedsPath      = $phinxPath . 'seeds' . $di;
            if (is_dir($migrationsPath)) {
                $phinxConfig['migrations'][] = $migrationsPath;
            }
            if (is_dir($seedsPath)) {
                $phinxConfig['seeds'][] = $seedsPath;
            }
            return $phinxConfig;
        }

        $vendorPackages = (array)($this->vendorPackages ?? $this->getVendorPackages());
        if (!isset($vendorPackages[$packageName])) {
            return $phinxConfig;
        }
        $phinxPaths = $vendorPackages[$packageName]['extra'][$this->extraName] ?? [];
        if (is_string($phinxPaths) && !empty($phinxPaths)) {
            $phinxPathArray[] = $phinxPaths;
        } else {
            $phinxPathArray = $phinxPaths;
        }
        if (!is_array($phinxPathArray) || count($phinxPathArray) < 1) {
            return $phinxConfig;
        }

        foreach ($phinxPathArray as $phinxPath) {
            $phinxPath = $this->vendorPath
                . rtrim(ltrim(str_replace(['/', '\\'], [$di, $di], $packageName), $di), $di) . $di
                . rtrim(ltrim(str_replace(['/', '\\'], [$di, $di], $phinxPath), $di), $di) . $di;
            if (!is_dir($phinxPath)) {
                continue;
            }
            $migrationsPath = $phinxPath . 'migrations' . $di;
            $seedsPath      = $phinxPath . 'seeds' . $di;
            if (is_dir($migrationsPath)) {
                $phinxConfig['migrations'][] = $migrationsPath;
            }
            if (is_dir($seedsPath)) {
                $phinxConfig['seeds'][] = $seedsPath;
            }
        }
        return $phinxConfig;
    }

    /**
     * 获取本地phinx路径
     * @return string
     */
    protected function getLocalPhinxPath(): string
    {
        $di        = DIRECTORY_SEPARATOR;
        $localPath = $this->getPhinxConfig('local')['phinx_path'];
        $localPath = rtrim(str_replace(['/', '\\'], [$di, $di], $localPath), $di) . $di;
        return $localPath;
    }

    /**
     * phinx目录检查
     * @param string $type 类型（migrations|seeds）
     * @return string
     * @throws \think\Exception
     */
    protected function ensureDirectory(string $type): string
    {
        $path = $this->getLocalPhinxPath() . $type . DIRECTORY_SEPARATOR;

        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new Exception(sprintf('directory "%s" does not exist', $path));
        }

        if (!is_writable($path)) {
            throw new Exception(sprintf('directory "%s" is not writable', $path));
        }
        return realpath($path);
    }
}