<?php

/*
 * This file is part of the PrestashopConsole package.
 *
 * (c) Matthieu Mota <matthieu@boxydev.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Boxydev\Prestashop\Command;

use Boxydev\Prestashop\Application;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PrestashopInstallCommand extends Command
{
    /**
     * @var ProgressBar
     */
    private $progressBar;

    /**
     * @var OutputInterface
     */
    private $output;

    protected function configure()
    {
        $this->setName('prestashop:install')
            ->setDescription('Install Prestashop.')
            ->setHelp('This command can install Prestashop.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        if (!file_exists(Application::ROOT_DIR.'prestashop.zip')) {
            $output->writeln("<info>Downloading Prestashop...</info>");

            $client = new Client();
            $request = $client->request(
                'GET',
                'https://www.prestashop.com/download/old/prestashop_1.7.2.4.zip',
                [
                    'progress' => function ($downloadSize, $downloaded) {
                        $this->progress($downloadSize, $downloaded);
                    },
                    'sink' => Application::ROOT_DIR.'prestashop.zip'
                ]
            );

            if (null !== $this->progressBar) {
                $this->progressBar->finish();
            }

            $output->writeln("\n");
        }

        $zip = new \ZipArchive();

        if (true !== $zip->open(Application::ROOT_DIR.'prestashop.zip')) {
            $output->writeln("<error>Unable to unzip ".Application::ROOT_DIR."prestashop.zip</error>");
            return;
        }

        $zip->extractTo(Application::ROOT_DIR.'prestashop');

        if (true !== $zip->open(Application::ROOT_DIR.'prestashop/prestashop.zip')) {
            $output->writeln("<error>Unable to unzip ".Application::ROOT_DIR."prestashop.zip</error>");
            return;
        }

        $output->writeln("<info>Extracting Prestashop...</info>");
        $zip->extractTo(Application::ROOT_DIR.'prestashop');

        $output->writeln("<info>Prestashop is installed !</info>");
    }

    private function progress($downloadSize, $downloaded)
    {
        if ($downloadSize < 1 * 1024 * 1024) {
            return;
        }

        if (null === $this->progressBar) {
            ProgressBar::setPlaceholderFormatterDefinition('max', function (ProgressBar $bar) {
                return Helper::formatMemory($bar->getMaxSteps());
            });

            ProgressBar::setPlaceholderFormatterDefinition('current', function (ProgressBar $bar) {
                return Helper::formatMemory($bar->getProgress());
            });

            $this->progressBar = new ProgressBar($this->output, $downloadSize);
            $this->progressBar->setFormat('%current%/%max% [%bar%]  %percent:3s%%');

            $this->progressBar->setProgressCharacter("\xF0\x9F\x8D\xBA");

            $this->progressBar->start();
        }

        $this->progressBar->setProgress($downloaded);
    }
}