<?php
/**
 *  ==================================================================
 *        文 件 名: Migrator.php
 *        概    要: Trait Migrator
 *        作    者: IT小强
 *        创建时间: 2019-10-14 11:07
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace itxq\phinx\traits;

use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Table;

/**
 * Trait Migrator
 * @package itxq\phinx\traits
 */
trait Migrator
{
    protected $MyISAM = 'MyISAM';
    protected $InnoDB = 'InnoDB';
    protected $UTF8 = 'utf8';
    protected $UTF8mb4 = 'utf8mb4';

    /**
     * 获取默认数据表配置
     * @param string $comment
     * @param array  $options
     * @return array
     */
    protected function getTableOptions(string $comment = '', array $options = []): array
    {
        $defaultOptions = [
            'id'          => true,
            'signed'      => false,
            'primary_key' => ['id'],
            'comment'     => $comment,
            'engine'      => $this->InnoDB,
            'character'   => $this->UTF8mb4,
            'collation'   => 'utf8mb4_general_ci',
            // MyISAM 支持
            'row_format'  => 'Dynamic',
        ];
        $options        = array_merge($defaultOptions, $options);
        return $options;
    }

    /**
     * PHP 秒级时间戳
     * @param string $comment
     * @param array  $options
     * @return array
     */
    protected function getIntTimeStampOptions(string $comment = '', array $options = []): array
    {
        $defaultOptions = [
            'limit'   => 10,
            'comment' => $comment,
            'signed'  => true,
            'null'    => true,
            'default' => null,
        ];
        $options        = array_merge($defaultOptions, $options);
        return $options;
    }

    /**
     * 整型配置
     * @param string $comment 字段注释
     * @param array  $options 更多选项配置
     * @return array
     */
    protected function getIntegerOptions(string $comment = '', array $options = []): array
    {
        $defaultOptions = [
            'signed'  => false,
            'length'  => 11,
            'default' => 0,
            'null'    => false,
            'comment' => $comment
        ];
        $options        = array_merge($defaultOptions, $options);
        return $options;
    }

    /**
     * 定点型配置
     * @param string $comment 字段注释
     * @param array  $options 更多选项配置
     * @return array
     */
    protected function getDecimalOptions(string $comment = '', array $options = []): array
    {
        $defaultOptions = [
            'signed'    => false,
            'precision' => 10,
            'scale'     => 2,
            'default'   => 0.00,
            'null'      => false,
            'comment'   => $comment
        ];
        $options        = array_merge($defaultOptions, $options);
        return $options;
    }

    /**
     * 字符串配置
     * @param string $comment 字段注释
     * @param array  $options 更多选项配置
     * @return array
     */
    protected function getStringOptions(string $comment = '', array $options = []): array
    {
        $defaultOptions = [
            'length'  => 191,
            'default' => '',
            'null'    => false,
            'comment' => $comment
        ];
        $options        = array_merge($defaultOptions, $options);
        return $options;
    }

    /**
     * 文本配置
     * @param string $comment 字段注释
     * @param array  $options 更多选项配置
     * @return array
     */
    protected function getTextOptions(string $comment = '', array $options = []): array
    {
        $defaultOptions = [
            'default' => null,
            'null'    => true,
            'comment' => $comment
        ];
        $options        = array_merge($defaultOptions, $options);
        return $options;
    }

    /**
     * 添加公共字段
     * @param \Phinx\Db\Table $table
     * @param bool|string     $id
     * @param null|array      $fields ['sort', 'version', 'create_by', 'create_time', 'update_by', 'update_time', 'delete_time']
     * @return \Phinx\Db\Table
     */
    protected function addCommonFields(Table $table, $id = true, ?array $fields = null): Table
    {
        // 排序数值
        if ($fields === null || in_array('sort', $fields, true)) {
            $table = $table->addColumn(
                'sort',
                AdapterInterface::PHINX_TYPE_INTEGER,
                $this->getIntegerOptions('排序数值', ['signed' => true])
            )->addIndex(['sort']);
        }

        // 乐观锁
        if ($fields === null || in_array('lock_version', $fields, true)) {
            $table = $table->addColumn(
                'lock_version',
                AdapterInterface::PHINX_TYPE_INTEGER,
                $this->getIntTimeStampOptions('乐观锁')
            )->addIndex(['lock_version']);
        }

        // 创建人
        if ($fields === null || in_array('create_by', $fields, true)) {
            $table = $table->addColumn(
                'create_by',
                AdapterInterface::PHINX_TYPE_INTEGER,
                $this->getIntegerOptions('创建人')
            )->addIndex(['create_by']);
        }

        // 创建时间
        if ($fields === null || in_array('create_time', $fields, true)) {
            $table = $table->addColumn('create_time',
                AdapterInterface::PHINX_TYPE_INTEGER,
                $this->getIntTimeStampOptions('创建时间')
            )->addIndex(['create_time']);
        }

        // 更新人
        if ($fields === null || in_array('update_by', $fields, true)) {
            $table = $table->addColumn(
                'update_by',
                AdapterInterface::PHINX_TYPE_INTEGER,
                $this->getIntegerOptions('更新人')
            )->addIndex(['update_by']);
        }

        // 更新时间
        if ($fields === null || in_array('update_time', $fields, true)) {
            $table = $table->addColumn('update_time',
                AdapterInterface::PHINX_TYPE_INTEGER,
                $this->getIntTimeStampOptions('更新时间')
            )->addIndex(['update_time']);
        }

        // 删除时间
        if ($fields === null || in_array('delete_time', $fields, true)) {
            $table = $table->addColumn(
                'delete_time',
                AdapterInterface::PHINX_TYPE_INTEGER,
                $this->getIntTimeStampOptions('删除时间')
            )->addIndex(['delete_time']);
        }

        // 主键索引
        if ($id !== false) {
            $id    = is_string($id) ? $id : 'id';
            $table = $table->addIndex([$id], ['unique' => true]);
        }

        return $table;
    }
}