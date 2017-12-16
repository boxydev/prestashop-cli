<?php

/*
 * This file is part of the PrestashopConsole package.
 *
 * (c) Matthieu Mota <matthieu@boxydev.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Boxydev\Command;

use Boxydev\Prestashop\Application;
use Boxydev\Prestashop\Command\PrestashopInstallCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class PrestashopInstallCommandTest extends TestCase
{
    public function testInstallPrestashop17()
    {
        $application = new Application();
        $application->add(new PrestashopInstallCommand());

        $command = $application->find('prestashop:install');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'directory' => 'prestashop'
        ));

        $output = $commandTester->getDisplay();
        $this->assertContains('Prestashop 1.7.2.4 is now installed', $output);
    }
}