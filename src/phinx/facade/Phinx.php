<?php
/**
 *  ==================================================================
 *        文 件 名: Phinx.php
 *        概    要: Phinx
 *        作    者: IT小强
 *        创建时间: 2019/11/23 16:45
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace itxq\phinx\facade;

use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Table;
use think\Facade;

/**
 * Class Phinx
 * @method Table table(string $tableName, array $options = [], string $package = self::LOCAL, ?array $adapterOptions = null) static 获取Phinx\Db\Table实例
 * @method AdapterInterface getAdapter(string $package = self::LOCAL, ?array $options = null) static Get an adapter
 * @method array getPhinxConfig(string $package = self::LOCAL) static 获取配置
 * @see     \itxq\phinx\Phinx
 * @mixin      \itxq\phinx\Phinx
 * @package itxq\phinx\facade
 */
class Phinx extends Facade
{
    /**
     * 获取当前Facade对应类名（或者已经绑定的容器对象标识）
     * @access protected
     * @return string
     */
    protected static function getFacadeClass(): string
    {
        return \itxq\phinx\Phinx::class;
    }
}