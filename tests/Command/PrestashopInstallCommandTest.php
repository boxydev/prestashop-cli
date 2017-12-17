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
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class PrestashopInstallCommandTest extends TestCase
{
    /**
     * @var Client Client with mock handler
     */
    private static $client;

    public static function setUpBeforeClass()
    {
        // $this->rootDir = realpath(__DIR__.'/../../');

        $prestashop17zip = Psr7\stream_for(fopen(__DIR__.'/../fixtures/prestashop_1.7.2.4.zip', 'r'));
        $prestashop16zip = Psr7\stream_for(fopen(__DIR__.'/../fixtures/prestashop_1.6.1.17.zip', 'r'));

        $mock = new MockHandler([
            new Response(200, [], $prestashop16zip),
            new Response(200, [], $prestashop17zip),
            new Response(200, [], $prestashop17zip)
        ]);
        $handler = HandlerStack::create($mock);
        self::$client = new Client(['handler' => $handler]);
    }

    public function testGetterSetter()
    {
        $prestashopInstallCommand = new PrestashopInstallCommand();
        $prestashopInstallCommand->setClient(new Client());
        $prestashopInstallCommand->setZip(new \ZipArchive());

        $this->assertInstanceOf(Client::class, $prestashopInstallCommand->getClient());
        $this->assertInstanceOf(\ZipArchive::class, $prestashopInstallCommand->getZip());
    }

    public function testInstallPrestashop17()
    {
        $application = new Application();
        $application->add(new PrestashopInstallCommand());

        $command = $application->find('prestashop:install');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'directory' => 'testDIR'
        ));

        $output = $commandTester->getDisplay();
        $this->assertContains('Prestashop 1.7.2.4 is now installed', $output);
    }

    public function testInstallPrestashop16()
    {
        $application = new Application();
        $prestashopInstallCommand = new PrestashopInstallCommand();
        $prestashopInstallCommand->setClient(self::$client);
        $application->add($prestashopInstallCommand);

        $command = $application->find('prestashop:install');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'directory' => 'testDIR',
            'version' => '1.6'
        ));

        $output = $commandTester->getDisplay();
        $this->assertContains('Prestashop 1.6.1.17 is now installed', $output);
    }

    public function testInstallInvalidVersion()
    {
        $this->expectException(\Exception::class);

        $application = new Application();
        $application->add(new PrestashopInstallCommand());

        $command = $application->find('prestashop:install');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'directory' => 'testDIR',
            'version' => '1.9'
        ));
    }

    public function testIfZipIsNotCorrect()
    {
        $application = new Application();
        $prestashopInstallCommand = new PrestashopInstallCommand();
        $prestashopInstallCommand->setClient(self::$client);
        $zip = $this->getMockBuilder('ZipArchive')->getMock();
        $zip->method('open')->will($this->returnValue(false));
        $prestashopInstallCommand->setZip($zip);
        $application->add($prestashopInstallCommand);

        $command = $application->find('prestashop:install');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'directory' => 'testDIR'
        ));

        $output = $commandTester->getDisplay();
        $this->assertContains('Unable to unzip', $output);
    }

    public function testIfZipIsNotCorrectFor17()
    {
        $application = new Application();
        $prestashopInstallCommand = new PrestashopInstallCommand();
        $prestashopInstallCommand->setClient(self::$client);
        $zip = $this->getMockBuilder('ZipArchive')->getMock();
        $zip->expects($this->exactly(2))
            ->method('open')
            ->will($this->onConsecutiveCalls(true, false));
        $prestashopInstallCommand->setZip($zip);
        $application->add($prestashopInstallCommand);

        $command = $application->find('prestashop:install');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'directory' => 'testDIR'
        ));

        $output = $commandTester->getDisplay();
        $this->assertContains('Unable to unzip', $output);
    }
}