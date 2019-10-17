<?php
/**
 *  ==================================================================
 *        文 件 名: Command.php
 *        概    要: 命令扩展
 *        作    者: IT小强
 *        创建时间: 2019-10-14 18:31
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace itxq\phinx\service;

use itxq\phinx\migrate\Create;
use itxq\phinx\migrate\Rollback;
use itxq\phinx\migrate\Run;
use think\Service;

/**
 * 命令扩展
 * Class Command
 * @package itxq\phinx\service
 */
class Command extends Service
{
    /**
     * 命令扩展
     */
    public function boot(): void
    {
        $this->commands([
            // 执行迁移
            Run::class,
            // 执行回滚
            Rollback::class,
            // 创建迁移文件
            Create::class
        ]);
    }
}