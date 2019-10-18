<?php
/**
 *  ==================================================================
 *        文 件 名: Create.php
 *        概    要: 创建迁移文件
 *        作    者: IT小强
 *        创建时间: 2019-10-14 10:16
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace itxq\phinx\migrate;

use Phinx\Util\Util;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Exception;

/**
 * 创建迁移文件
 * Class Create
 * @package itxq\phinx\migrate
 */
class Create extends Base
{
    /**
     * 设置指令
     */
    protected function configure(): void
    {
        $this->setName('migrate:create')
            ->setDescription('Create a new migration')
            ->addArgument('name', Argument::REQUIRED, 'What is the name of the migration?')
            ->setHelp(sprintf('%sCreates a new database migration%s', PHP_EOL, PHP_EOL));
    }

    /**
     * 执行指令
     * @param \think\console\Input  $input
     * @param \think\console\Output $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(Input $input, Output $output): void
    {
        $className = $input->getArgument('name');

        $path = $this->create($className);

        $output->writeln('<info>created</info> .' . str_replace(getcwd(), '', realpath($path)));
    }

    /**
     * 创建迁移文件
     * @param string $className
     * @return string
     * @throws \think\Exception
     * @throws \Exception
     */
    protected function create(string $className): string
    {
        $path = $this->ensureDirectory();

        if (!Util::isValidPhinxClassName($className)) {
            throw new Exception(sprintf('The migration class name "%s" is invalid. Please use CamelCase format.',
                $className));
        }

        if (!Util::isUniqueMigrationClassName($className, $path)) {
            throw new Exception(sprintf('The migration class name "%s" already exists', $className));
        }

        $trueClass = $this->mapClassNameToFileName($className);
        // Compute the file path
        $fileName = $trueClass . '.php';
        $filePath = $path . DIRECTORY_SEPARATOR . $fileName;

        if (is_file($filePath)) {
            throw new Exception(sprintf('The file "%s" already exists', $filePath));
        }

        // Verify that the template creation class (or the aliased class) exists and that it implements the required interface.
        $aliasedClassName = null;

        // Load the alternative template if it is defined.
        $contents = file_get_contents($this->getTemplate());

        // inject the class names appropriate to this migration
        $contents = strtr($contents, [
            'MigratorClass' => parse_name($className, 1),
        ]);

        if (false === file_put_contents($filePath, $contents)) {
            throw new Exception(sprintf('The file "%s" could not be written to', $path));
        }

        return $filePath;
    }

    /**
     * migrate目录检查
     * @return string
     * @throws \think\Exception
     */
    protected function ensureDirectory(): string
    {
        $path = $this->getLocalPhinxPath() . 'migrations' . DIRECTORY_SEPARATOR;

        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new Exception(sprintf('directory "%s" does not exist', $path));
        }

        if (!is_writable($path)) {
            throw new Exception(sprintf('directory "%s" is not writable', $path));
        }
        return realpath($path);
    }

    /**
     * 获取migrate模板路径
     * @return string
     */
    protected function getTemplate(): string
    {
        return __DIR__ . '/../stubs/migrate.stub';
    }
}