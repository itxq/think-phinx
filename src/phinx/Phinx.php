<?php
/**
 *  ==================================================================
 *        文 件 名: Phinx.php
 *        概    要: Phinx
 *        作    者: IT小强
 *        创建时间: 2019/11/23 15:50
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace itxq\phinx;

use Phinx\Db\Adapter\AdapterFactory;
use Phinx\Db\Adapter\AdapterInterface;

/**
 * Class Phinx
 * @package itxq\phinx
 */
class Phinx
{
    /**
     * @var string local
     */
    public const LOCAL = 'local';

    /**
     * Get an adapter
     * @param string     $package 包名称
     * @param array|null $options
     * @return \Phinx\Db\Adapter\AdapterInterface
     */
    public static function getAdapter(string $package = self::LOCAL, ?array $options = null): AdapterInterface
    {
        $options = $options ?? self::getPhinxConfig($package);
        $adapter = AdapterFactory::instance()->getAdapter($options['adapter'], $options);
        if ($adapter->hasOption('table_prefix') || $adapter->hasOption('table_suffix')) {
            $adapter = AdapterFactory::instance()->getWrapper('prefix', $adapter);
        }
        return $adapter;
    }

    /**
     * 获取配置
     * @param string $package
     * @return array
     */
    public static function getPhinxConfig(string $package = self::LOCAL): array
    {
        $default = app()->config->get('database.default');

        $config = app()->config->get("database.connections.{$default}");

        if (0 === (int)$config['deploy']) {
            $phinxConfig = [
                'adapter'      => $config['type'],
                'host'         => $config['hostname'],
                'name'         => $config['database'],
                'user'         => $config['username'],
                'pass'         => $config['password'],
                'port'         => $config['hostport'],
                'charset'      => $config['charset'],
                'table_prefix' => $config['prefix'],
            ];
        } else {
            $phinxConfig = [
                'adapter'      => explode(',', $config['type'])[0],
                'host'         => explode(',', $config['hostname'])[0],
                'name'         => explode(',', $config['database'])[0],
                'user'         => explode(',', $config['username'])[0],
                'pass'         => explode(',', $config['password'])[0],
                'port'         => explode(',', $config['hostport'])[0],
                'charset'      => explode(',', $config['charset'])[0],
                'table_prefix' => explode(',', $config['prefix'])[0],
            ];
        }

        $phinxConfig = array_merge($phinxConfig, app()->config->get('phinx.database', []));

        $table = app()->config->get('phinx.migration_table', 'migration');
        if (!empty($package)) {
            $table .= '_' . strtolower(md5($package));
        }

        $defaultPhinxPath                       = realpath(app()->getRootPath()) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR;
        $phinxConfig['phinx_path']              = app()->config->get('phinx.phinx_path', $defaultPhinxPath);
        $phinxConfig['default_migration_table'] = $phinxConfig['table_prefix'] . $table;
        $phinxConfig['version_order']           = app()->config->get('phinx.version_order', 'creation');
        return $phinxConfig;
    }
}