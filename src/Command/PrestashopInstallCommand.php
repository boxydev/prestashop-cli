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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class PrestashopInstallCommand extends Command
{
    protected function configure()
    {
        $this->setName('prestashop:install')
            ->setDescription('Install Prestashop.')
            ->addOption('psVersion', null, InputOption::VALUE_OPTIONAL, 'Prestashop version')
            ->setHelp('This command can install Prestashop.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $psVersion = $input->getOption('psVersion');

        if (null === $psVersion) {
            $askVersion = new ChoiceQuestion('What version do you want to install ? [1.7.2.4]', [
                '1.6.1.17',
                '1.7.2.4'
            ], '1.7.2.4');
            $psVersion = $helper->ask($input, $output, $askVersion);
        }

        if (!file_exists(Application::ROOT_DIR.'prestashop.zip')) {
            $output->writeln("<info>Downloading Prestashop...</info>");

            $client = new Client();
            $progressBar = null;
            $client->request(
                'GET',
                'https://www.prestashop.com/download/old/prestashop_'.$psVersion.'.zip',
                [
                    'progress' => function ($downloadSize, $downloaded) use (&$output, &$progressBar) {
                        if ($downloadSize < 1 * 1024 * 1024) {
                            return;
                        }

                        if (null === $progressBar) {
                            ProgressBar::setPlaceholderFormatterDefinition('max', function (ProgressBar $bar) {
                                return Helper::formatMemory($bar->getMaxSteps());
                            });

                            ProgressBar::setPlaceholderFormatterDefinition('current', function (ProgressBar $bar) {
                                return Helper::formatMemory($bar->getProgress());
                            });

                            $progressBar = new ProgressBar($output, $downloadSize);
                            $progressBar->setFormat('%current%/%max% %bar%  %percent:3s%%');

                            // $progressBar->setProgressCharacter("\xF0\x9F\x8D\xBA");
                            $progressBar->setEmptyBarCharacter('░');
                            $progressBar->setProgressCharacter('');
                            $progressBar->setBarCharacter('▓');

                            $progressBar->start();
                        }

                        $progressBar->setProgress($downloaded);
                    },
                    'sink' => Application::ROOT_DIR.'prestashop.zip'
                ]
            );

            if (null !== $progressBar) {
                $progressBar->finish();
            }

            $output->writeln("\n");
        }

        $output->writeln("<info>Extracting Prestashop...</info>");

        $zip = new \ZipArchive();

        if (true !== $zip->open(Application::ROOT_DIR.'prestashop.zip')) {
            $output->writeln("<error>Unable to unzip ".Application::ROOT_DIR."prestashop.zip</error>");
            return;
        }

        $zip->extractTo(Application::ROOT_DIR.'prestashop');

        if (version_compare($psVersion, '1.7', '>=')) {
            if (true !== $zip->open(Application::ROOT_DIR.'prestashop/prestashop.zip')) {
                $output->writeln("<error>Unable to unzip ".Application::ROOT_DIR."prestashop.zip</error>");
                return;
            }

            $zip->extractTo(Application::ROOT_DIR.'prestashop');
        }

        $output->writeln("<info>Prestashop is installed !</info>");
    }
}