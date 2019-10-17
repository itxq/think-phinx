<?php
/**
 *  ==================================================================
 *        文 件 名: Command.php
 *        概    要: 命令行基类
 *        作    者: IT小强
 *        创建时间: 2019-10-12 16:04
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace itxq\phinx;

use think\console\Input;
use think\console\Output;

/**
 * 命令行基类
 * Class Command
 * @package itxq\phinx
 */
abstract class Command extends \think\console\Command
{
    /**
     * @var string vendor路径
     */
    protected $vendorPath = '';

    /**
     * @var array|null 包信息
     */
    protected $vendorPackages;

    /**
     * 初始化
     * @param \think\console\Input  $input
     * @param \think\console\Output $output
     */
    protected function initialize(Input $input, Output $output): void
    {
        parent::initialize($input, $output);
        $this->vendorPath = realpath(app()->getRootPath()) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;
    }

    /**
     * 获取全部包信息
     * @return array
     */
    protected function getVendorPackages(): array
    {
        if ($this->vendorPackages !== null) {
            return $this->vendorPackages;
        }
        $composerFile = $this->vendorPath . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'installed.json';
        if (!is_file($composerFile)) {
            return [];
        }
        $packagesInfo = @json_decode(@file_get_contents($composerFile), true);
        if (!is_array($packagesInfo)) {
            return [];
        }
        $packages = [];
        foreach ($packagesInfo as $packageInfo) {
            $packages[$packageInfo['name']] = $packageInfo;
        }
        $this->vendorPackages = $packages;
        return $this->vendorPackages;
    }
}
