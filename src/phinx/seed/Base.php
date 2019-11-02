<?php
/**
 *  ==================================================================
 *        文 件 名: Base.php
 *        概    要: seed 命令行基类
 *        作    者: IT小强
 *        创建时间: 2019-11-02 14:16
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace itxq\phinx\seed;

use itxq\phinx\Phinx;
use Phinx\Seed\AbstractSeed;
use Phinx\Util\Util;
use think\Exception;

/**
 * seed 命令行基类
 * Class Base
 * @package itxq\phinx\seed
 */
abstract class Base extends Phinx
{
    /**
     * @var array seed 实例列表
     */
    protected $seeds = [];

    /**
     * 获取seeds
     * @param string $package
     * @return mixed|AbstractSeed[]
     * @throws \think\Exception
     */
    public function getSeeds(string $package)
    {
        if (isset($this->seeds[$package])) {
            return $this->seeds[$package];
        }
        $path     = $this->getPhinxPaths($package);
        $phpFiles = $this->getPhpFile($path['seeds']);

        // filter the files to only get the ones that match our naming scheme
        // $fileNames = [];
        /** @var AbstractSeed[] $seeds */
        $seeds = [];

        foreach ($phpFiles as $filePath) {
            if (Util::isValidSeedFileName(basename($filePath))) {
                // convert the filename to a class name
                $class = pathinfo($filePath, PATHINFO_FILENAME);
                // $fileNames[$class] = basename($filePath);

                // load the seed file
                /** @noinspection PhpIncludeInspection */
                require_once $filePath;
                if (!class_exists($class)) {
                    throw new Exception(sprintf('Could not find class "%s" in file "%s"', $class, $filePath));
                }

                // instantiate it
                $seed = new $class();

                if (!($seed instanceof AbstractSeed)) {
                    throw new Exception(sprintf('The class "%s" in file "%s" must extend \Phinx\Seed\AbstractSeed',
                        $class, $filePath));
                }
                $seeds[$class] = $seed;
            }
        }

        ksort($seeds);
        $this->seeds[$package] = $seeds;
        return $seeds;
    }
}