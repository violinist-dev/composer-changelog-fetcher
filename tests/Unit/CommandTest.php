<?php

namespace Violinist\ChangelogFetcher\Tests\Unit;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Violinist\ChangelogFetcher\ChangelogRetriever;
use Violinist\ChangelogFetcher\FetchCommand;
use Violinist\GitLogFormat\ChangeLogData;

class CommandTest extends TestBase
{
    public function testCommandNoArguments()
    {
        $mock_retriever = $this->createMock(ChangelogRetriever::class);
        $command = new FetchCommand($mock_retriever);
        // Just configure it and see it works ok.
        $command_tester = new CommandTester($command);
        $this->expectException(\InvalidArgumentException::class);
        $command_tester->execute([]);
    }

    public function testCommand()
    {
        $mock_log = new ChangeLogData();
        $mock_retriever = $this->createMock(ChangelogRetriever::class);
        $mock_retriever->expects($this->once())
            ->method('retrieveChangelog')
            ->willReturn($mock_log);
        $command = new FetchCommand($mock_retriever);
        // Just configure it and see it works ok.
        $command_tester = new CommandTester($command);
        $command_tester->execute([
            '--package' => 'psr/log',
            '--version_from' => '1.0.0',
            '--version_to' => '1.0.2',
            '--directory' => getcwd(),
        ]);
    }
}
