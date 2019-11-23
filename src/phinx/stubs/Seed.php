<?php
/**
 *  ==================================================================
 *        文 件 名: Seed.php
 *        概    要: Seed
 *        作    者: IT小强
 *        创建时间: 2019/11/23 17:12
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace itxq\phinx\stubs;

use itxq\phinx\traits\Migrator;
use Phinx\Seed\AbstractSeed;

/**
 * Class Seed
 * @package itxq\phinx\stubs
 */
class Seed extends AbstractSeed
{
    use Migrator;
}