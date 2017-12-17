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

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class PrestashopInstallCommand extends Command
{
    /**
     * @var Filesystem To manage downloaded files
     */
    private $fs;

    /**
     * @var \ZipArchive To manage zip files
     */
    private $zip;

    /**
     * @var Client
     */
    private $client;

    protected function configure()
    {
        $this->setName('prestashop:install')
            ->setDescription('Install Prestashop.')
            ->addArgument('directory', InputArgument::REQUIRED, 'Directory where Prestashop will be installed.')
            ->addArgument('version', InputArgument::OPTIONAL, 'Prestashop version to be installed (default to 1.7 but can be 1.6).', '1.7')
            ->setHelp('This command can install Prestashop.');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->fs = new Filesystem();
        $this->client = (null === $this->client) ? new Client() : $this->client;
        $this->zip = (null === $this->zip) ? new \ZipArchive() : $this->zip;
    }

    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function setZip(\ZipArchive $zip)
    {
        $this->zip = $zip;
    }

    public function getZip()
    {
        return $this->zip;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $version = trim($input->getArgument('version'));
        $directory = rtrim(trim($input->getArgument('directory')), DIRECTORY_SEPARATOR);
        $psDirectory = $this->fs->isAbsolutePath($directory) ? $directory : getcwd().DIRECTORY_SEPARATOR.$directory;

        if ('1.6' !== $version && '1.7' !== $version) {
            throw new \Exception('Prestashop version must be 1.6 or 1.7.');
        }

        $version = ('1.6' === $version) ? '1.6.1.17' : '1.7.2.4';

        /*$helper = $this->getHelper('question');
        if (null === $psVersion) {
            $askVersion = new ChoiceQuestion('What version do you want to install ? [1.7.2.4]', [
                '1.6.1.17',
                '1.7.2.4'
            ], '1.7.2.4');
            $psVersion = $helper->ask($input, $output, $askVersion);
        }*/

        $zipPrestashop = $psDirectory.'/prestashop_'.$version.'.zip';

        $output->writeln("<info>Downloading Prestashop ".$version."...</info>");

        $this->fs->mkdir($psDirectory);

        $progressBar = null;
        $this->client->request(
            'GET',
            'https://www.prestashop.com/download/old/prestashop_'.$version.'.zip',
            [
                'progress' => function ($downloadSize, $downloaded) use (&$output, &$progressBar, $version) {
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
                'sink' => $zipPrestashop
            ]
        );

        if (null !== $progressBar) {
            $progressBar->finish();
        }

        $output->writeln("\n");

        $output->writeln("<info>Extracting Prestashop ".$version."...</info>");

        if (true !== $this->zip->open($zipPrestashop)) {
            $output->writeln("<error>Unable to unzip ".$zipPrestashop."</error>");
            return;
        }

        // Unzip strategy for 1.6
        if (version_compare($version, '1.7', '<=')) {
            for ($i = 0; $i < $this->zip->numFiles; $i++) {
                $filename = $this->zip->getNameIndex($i);

                if ('Install_PrestaShop.html' !== $filename) {
                    $this->zip->extractTo($psDirectory, $filename);
                }
            }
        }

        // Unzip strategy for 1.7 because distribution zip contains a zip
        if (version_compare($version, '1.7', '>=')) {
            for ($i = 0; $i < $this->zip->numFiles; $i++) {
                $filename = $this->zip->getNameIndex($i);

                if ('prestashop.zip' === $filename) {
                    $this->zip->extractTo($psDirectory, $filename);
                }
            }

            if (true !== $this->zip->open($psDirectory.'/prestashop.zip')) {
                $output->writeln("<error>Unable to unzip ".$zipPrestashop."</error>");
                return;
            }

            $this->zip->extractTo($psDirectory);

            $this->fs->remove($psDirectory.'/prestashop.zip');
        }

        $this->zip->close();
        $this->fs->remove($zipPrestashop);

        $output->writeln("<info>Prestashop ".$version." is now installed at ".$psDirectory." !</info>");
    }
}