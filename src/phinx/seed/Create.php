<?php
/**
 *  ==================================================================
 *        文 件 名: Create.php
 *        概    要: 创建 seeder 文件
 *        作    者: IT小强
 *        创建时间: 2019-11-02 14:18
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace itxq\phinx\seed;

use Phinx\Util\Util;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Exception;

/**
 * 创建 seeder 文件
 * Class Create
 * @package itxq\phinx\seed
 */
class Create extends Base
{
    /**
     * 设置指令
     */
    protected function configure(): void
    {
        $this->setName('seed:create')
            ->setDescription('Create a new database seeder')
            ->addArgument('name', Argument::REQUIRED, 'What is the name of the seeder?')
            ->setHelp(sprintf('%sCreates a new database seeder%s', PHP_EOL, PHP_EOL));
    }

    /**
     * 执行指令
     * @param \think\console\Input  $input
     * @param \think\console\Output $output
     * @throws \Exception
     */
    protected function execute(Input $input, Output $output): void
    {

        $path = $this->ensureDirectory('seeds');

        $className = $input->getArgument('name');

        if (!Util::isValidPhinxClassName($className)) {
            throw new Exception(
                sprintf('The seed class name "%s" is invalid. Please use CamelCase format', $className)
            );
        }

        $className = 'Seed' . date('YmdHis') . $className;

        // Compute the file path
        $filePath = $path . DIRECTORY_SEPARATOR . $className . '.php';

        if (is_file($filePath)) {
            throw new Exception(sprintf('The file "%s" already exists', basename($filePath)));
        }

        // inject the class names appropriate to this seeder
        $contents = file_get_contents($this->getTemplate());
        $classes  = [
            'SeederClass' => $className,
        ];
        $contents = strtr($contents, $classes);

        if (false === file_put_contents($filePath, $contents)) {
            throw new Exception(sprintf('The file "%s" could not be written to', $path));
        }

        $output->writeln('<info>created</info> .' . str_replace(getcwd(), '', $filePath));
    }

    /**
     * 获取seed模板路径
     * @return string
     */
    protected function getTemplate(): string
    {
        return __DIR__ . '/../stubs/seed.stub';
    }
}