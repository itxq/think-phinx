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

use Phinx\Db\Adapter\AdapterInterface;
use think\Exception;
use \itxq\phinx\facade\Phinx;

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
        $this->adapter[$package] = Phinx::getAdapter($package);
        return $this->adapter[$package];
    }

    /**
     * 获取配置
     * @param string $package
     * @return array
     */
    protected function getPhinxConfig(string $package): array
    {
        return Phinx::getPhinxConfig($package);
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
        if ($packageName === Phinx::LOCAL) {
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
        $localPath = $this->getPhinxConfig(Phinx::LOCAL)['phinx_path'];
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