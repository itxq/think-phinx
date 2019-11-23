<?php
/**
 *  ==================================================================
 *        文 件 名: Run.php
 *        概    要: 执行 seed
 *        作    者: IT小强
 *        创建时间: 2019-11-02 14:44
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace itxq\phinx\seed;

use itxq\phinx\Phinx;
use Phinx\Seed\SeedInterface;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\Exception;

/**
 * 执行 seed
 * Class Run
 * @package itxq\phinx\seed
 */
class Run extends Base
{
    /**
     * 设置指令
     */
    protected function configure(): void
    {
        $this->setName('seed:run')
            ->setDescription('Run database seeders')
            ->addOption('seed', 's', Option::VALUE_REQUIRED, 'What is the name of the seeder?')
            ->addOption('package', 'p', Option::VALUE_REQUIRED, 'The package to migrate to')
            ->setHelp(<<<EOT
                The <info>seed:run</info> command runs all available or individual seeders

<info>php think seed:run</info>
<info>php think seed:run -s UserSeeder</info>
<info>php think seed:run -v</info>

EOT
            );
    }

    /**
     * 执行指令
     * @param \think\console\Input  $input
     * @param \think\console\Output $output
     * @throws \think\Exception
     */
    protected function execute(Input $input, Output $output): void
    {
        $seed        = $input->getOption('seed');
        $packageName = (string)$input->getOption('package');
        if (empty($packageName)) {
            // 迁移本地
            if (is_dir($this->getLocalPhinxPath() . 'seeds')) {
                $this->seed(Phinx::LOCAL, $output, $seed);
            }
            // 迁移所有包
            foreach ($this->getVendorPackages() as $packageName => $packageInfo) {
                if (!isset($packageInfo['extra'][$this->extraName])) {
                    continue;
                }
                $this->seed($packageName, $output, $seed);
            }
        } else {
            // 迁移指定包，local表示本地
            $this->seed($packageName, $output, $seed);
        }
    }

    /**
     * 执行 seed
     * @param string                $package
     * @param \think\console\Output $output
     * @param null                  $seed
     * @throws \think\Exception
     */
    public function seed(string $package, Output $output, $seed = null): void
    {
        $output->writeln('>>> <info>' . $package . ': <comment>seeding!</comment></info>');
        $start = microtime(true);
        $seeds = $this->getSeeds($package);
        if (null === $seed) {
            // run all seeders
            foreach ($seeds as $seeder) {
                /* @var  $seeder SeedInterface */
                if (array_key_exists($seeder->getName(), $seeds)) {
                    $this->executeSeed($seeder, $package);
                }
            }
        } else if (array_key_exists($seed, $seeds)) {
            $this->executeSeed($seeds[$seed], $package);
        } else {
            throw new Exception(sprintf('The seed class "%s" does not exist', $seed));
        }
        $end = microtime(true);
        $output->writeln('');
        $output->writeln('<comment>All Done. Took ' . sprintf('%.4fs', $end - $start) . '</comment>');
    }

    /**
     * 执行 seed
     * @param \Phinx\Seed\SeedInterface $seed
     * @param string                    $package
     */
    protected function executeSeed(SeedInterface $seed, string $package): void
    {
        $this->output->writeln('');
        $this->output->writeln(' ==' . ' <info>' . $seed->getName() . ':</info>' . ' <comment>seeding</comment>');

        // Execute the seeder and log the time elapsed.
        $start = microtime(true);
        $seed->setAdapter($this->getAdapter($package));

        // begin the transaction if the adapter supports it
        if ($this->getAdapter($package)->hasTransactions()) {
            $this->getAdapter($package)->beginTransaction();
        }

        // Run the seeder
        if (method_exists($seed, SeedInterface::RUN)) {
            $seed->run();
        }

        // commit the transaction if the adapter supports it
        if ($this->getAdapter($package)->hasTransactions()) {
            $this->getAdapter($package)->commitTransaction();
        }
        $end = microtime(true);

        $this->output->writeln(' ==' . ' <info>' . $seed->getName() . ':</info>' . ' <comment>seeded' . ' ' . sprintf('%.4fs',
                $end - $start) . '</comment>');
    }
}