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

use Cake\Database\Query;
use Phinx\Db\Adapter\AdapterFactory;
use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Table;
use think\App;

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
     * @var \think\App
     */
    protected $app;

    /**
     * Phinx constructor.
     * @param \think\App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * Execute查询
     * @param string     $sql
     * @param string     $package
     * @param array|null $adapterOptions
     * @return mixed
     */
    public function execute(string $sql, string $package = self::LOCAL, ?array $adapterOptions = null): int
    {
        return $this->getAdapter($package, $adapterOptions)->execute($sql);
    }

    /**
     * Query查询
     * @param string     $sql
     * @param string     $package
     * @param array|null $adapterOptions
     * @return mixed
     */
    public function query(string $sql, string $package = self::LOCAL, ?array $adapterOptions = null)
    {
        return $this->getAdapter($package, $adapterOptions)->query($sql);
    }

    /**
     * fetchRow
     * @param string     $sql
     * @param string     $package
     * @param array|null $adapterOptions
     * @return array
     */
    public function fetchRow(string $sql, string $package = self::LOCAL, ?array $adapterOptions = null): array
    {
        return $this->getAdapter($package, $adapterOptions)->fetchRow($sql);
    }

    /**
     * fetchAll
     * @param string     $sql
     * @param string     $package
     * @param array|null $adapterOptions
     * @return array
     */
    public function fetchAll(string $sql, string $package = self::LOCAL, ?array $adapterOptions = null): array
    {
        return $this->getAdapter($package, $adapterOptions)->fetchAll($sql);
    }

    /**
     * getQueryBuilder
     * @param string     $package
     * @param array|null $adapterOptions
     * @return \Cake\Database\Query
     */
    public function getQueryBuilder(string $package = self::LOCAL, ?array $adapterOptions = null): Query
    {
        return $this->getAdapter($package, $adapterOptions)->getQueryBuilder();
    }

    /**
     * 创建数据库
     * @param string     $name
     * @param            $options
     * @param string     $package
     * @param array|null $adapterOptions
     */
    public function createDatabase(string $name, $options, string $package = self::LOCAL, ?array $adapterOptions = null): void
    {
        $this->getAdapter($package, $adapterOptions)->createDatabase($name, $options);
    }

    /**
     * 删除数据库
     * @param string     $name
     * @param string     $package
     * @param array|null $adapterOptions
     */
    public function dropDatabase(string $name, string $package = self::LOCAL, ?array $adapterOptions = null): void
    {
        $this->getAdapter($package, $adapterOptions)->dropDatabase($name);
    }

    /**
     * 判断表是否存在
     * @param string     $tableName
     * @param string     $package
     * @param array|null $adapterOptions
     * @return bool
     */
    public function hasTable(string $tableName, string $package = self::LOCAL, ?array $adapterOptions = null): bool
    {
        return $this->getAdapter($package, $adapterOptions)->hasTable($tableName);
    }

    /**
     * 获取 Phinx\Db\Table 实例
     * @param string     $tableName 数据表名
     * @param array      $options   参数
     * @param string     $package
     * @param array|null $adapterOptions
     * @return \Phinx\Db\Table
     */
    public function table(string $tableName, array $options = [], string $package = self::LOCAL, ?array $adapterOptions = null): Table
    {
        return new Table($tableName, $options, $this->getAdapter($package, $adapterOptions));
    }

    /**
     * Get an adapter
     * @param string     $package 包名称
     * @param array|null $adapterOptions
     * @return \Phinx\Db\Adapter\AdapterInterface
     */
    public function getAdapter(string $package = self::LOCAL, ?array $adapterOptions = null): AdapterInterface
    {
        $options = $adapterOptions ?? $this->getPhinxConfig($package);
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
    public function getPhinxConfig(string $package = self::LOCAL): array
    {
        $default = $this->app->config->get('database.default');

        $config = $this->app->config->get("database.connections.{$default}");

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

        $phinxConfig = array_merge($phinxConfig, $this->app->config->get('phinx.database', []));

        $table = $this->app->config->get('phinx.migration_table', 'migration');
        if (!empty($package)) {
            $table .= '_' . strtolower(md5($package));
        }

        $defaultPhinxPath                       = realpath($this->app->getRootPath()) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR;
        $phinxConfig['phinx_path']              = $this->app->config->get('phinx.phinx_path', $defaultPhinxPath);
        $phinxConfig['default_migration_table'] = $phinxConfig['table_prefix'] . $table;
        $phinxConfig['version_order']           = $this->app->config->get('phinx.version_order', 'creation');
        return $phinxConfig;
    }
}