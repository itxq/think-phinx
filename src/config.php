<?php
// +----------------------------------------------------------------------
// | phinx 配置
// +----------------------------------------------------------------------

return [
    // 迁移记录表名称
    'migration_table'   => 'migration',
    // phinx根目录 生成文件时使用
    'phinx_path'    => realpath(app()->getRootPath()) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR,
    // Version 顺序
    // 当执行回滚或者打印迁移脚本状态时，Phinx 顺序通过 version_order 控制
    // creation （默认）：迁移脚本按照创建时间排序，也就是按照文件名排序
    // execution：迁移脚本按照执行顺序排序，也就是开始时间
    'version_order' => 'creation'
];