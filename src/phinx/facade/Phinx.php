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

use Cake\Database\Query;
use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Table;
use think\Facade;

/**
 * Class Phinx
 * @method int execute(string $sql, string $package = self::LOCAL, ?array $adapterOptions = null) static Execute查询
 * @method mixed query(string $sql, string $package = self::LOCAL, ?array $adapterOptions = null) static Query查询
 * @method array fetchRow(string $sql, string $package = self::LOCAL, ?array $adapterOptions = null) static fetchRow
 * @method array fetchAll(string $sql, string $package = self::LOCAL, ?array $adapterOptions = null) static fetchAll
 * @method Query getQueryBuilder(string $package = self::LOCAL, ?array $adapterOptions = null) static getQueryBuilder
 * @method void createDatabase(string $name, $options, string $package = self::LOCAL, ?array $adapterOptions = null) static 创建数据库
 * @method void dropDatabase(string $name, string $package = self::LOCAL, ?array $adapterOptions = null) static 删除数据库
 * @method bool hasTable(string $tableName, string $package = self::LOCAL, ?array $adapterOptions = null) static 判断表是否存在
 * @method Table table(string $tableName, array $options = [], string $package = self::LOCAL, ?array $adapterOptions = null) static 获取Phinx\Db\Table实例
 * @method AdapterInterface getAdapter(string $package = self::LOCAL, ?array $options = null) static Get an adapter
 * @method AdapterInterface setAdapter(string $package = self::LOCAL, ?array $adapterOptions = null) static Create an adapter
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