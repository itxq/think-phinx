<?php
/**
 *  ==================================================================
 *        文 件 名: Migration.php
 *        概    要: Migration
 *        作    者: IT小强
 *        创建时间: 2019/11/23 17:03
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace itxq\phinx\stubs;

use itxq\phinx\traits\Migrator;
use Phinx\Migration\AbstractMigration;

/**
 * Class Migration
 * @package itxq\phinx\stubs
 */
class Migration extends AbstractMigration
{
    use Migrator;
}