<?php

namespace Violinist\ChangelogFetcher\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Violinist\ChangelogFetcher\ChangelogRetriever;
use Violinist\ChangelogFetcher\DependencyRepoRetriever;
use Violinist\ProcessFactory\ProcessFactoryInterface;

class ChangelogRetrieverTest extends TestCase
{
    public function testGetChangeLog()
    {
        $fake_package_data = $this->getTestData();
        $mock_retriever = $this->getMockRetriever($fake_package_data);
        $mock_process = $this->createMock(Process::class);
        $mock_process->method('getExitCode')
            ->willReturn(0);
        $mock_process->method('getOutput')
            ->willReturn("ababab Change 1\nfefefe Change 2");
        $mock_process_factory = $this->createMock(ProcessFactoryInterface::class);
        $mock_process_factory->method('getProcess')
            ->with('git -C /tmp/dummy_path log 1.0.0..1.0.1 --oneline')
            ->willReturn($mock_process);
        $retriever = new ChangelogRetriever($mock_retriever, $mock_process_factory);
        $fake_lock = (object) [
            'packages' => [
               $fake_package_data,
            ],
        ];
        $log = $retriever->retrieveChangelog('psr/log', $fake_lock, '1.0.0', '1.0.1');
        $this->assertEquals('[{"hash":"ababab","message":"Change 1","link":"https:\/\/github.com\/psr\/log\/commit\/ababab"},{"hash":"fefefe","message":"Change 2","link":"https:\/\/github.com\/psr\/log\/commit\/fefefe"}]', $log->getAsJson());
    }

    public function testExit1GitLog()
    {
        $fake_package_data = $this->getTestData();
        $mock_retriever = $this->getMockRetriever($fake_package_data);
        $mock_process = $this->createMock(Process::class);
        $mock_process->method('getExitCode')
            ->willReturn(1);
        $mock_process_factory = $this->createMock(ProcessFactoryInterface::class);
        $mock_process_factory->method('getProcess')
            ->with('git -C /tmp/dummy_path log 1.0.0..1.0.1 --oneline')
            ->willReturn($mock_process);
        $retriever = new ChangelogRetriever($mock_retriever, $mock_process_factory);
        $fake_lock = (object) [
            'packages' => [
                $fake_package_data,
            ],
        ];
        $this->expectExceptionMessage('git log process exited with wrong exit code. Exit code was: 1');
        $log = $retriever->retrieveChangelog('psr/log', $fake_lock, '1.0.0', '1.0.1');
    }

    public function testEmptyChangelog()
    {
        $fake_package_data = $this->getTestData();
        $mock_retriever = $this->getMockRetriever($fake_package_data);
        $mock_process = $this->createMock(Process::class);
        $mock_process->method('getExitCode')
            ->willReturn(0);
        $mock_process_factory = $this->createMock(ProcessFactoryInterface::class);
        $mock_process_factory->method('getProcess')
            ->with('git -C /tmp/dummy_path log 1.0.0..1.0.1 --oneline')
            ->willReturn($mock_process);
        $retriever = new ChangelogRetriever($mock_retriever, $mock_process_factory);
        $fake_lock = (object) [
            'packages' => [
                $fake_package_data,
            ],
        ];
        $this->expectExceptionMessage('The changelog string was empty for package psr/log');
        $log = $retriever->retrieveChangelog('psr/log', $fake_lock, '1.0.0', '1.0.1');
    }

    public function testGitSourceSsh()
    {
        $fake_package_data = $this->getTestData();
        $fake_package_data->source->url = 'git@github.com:psr/log';
        $mock_retriever = $this->getMockRetriever($fake_package_data);
        $mock_process = $this->createMock(Process::class);
        $mock_process->method('getExitCode')
            ->willReturn(0);
        $mock_process->method('getOutput')
            ->willReturn('ababab Change 1');
        $mock_process_factory = $this->createMock(ProcessFactoryInterface::class);
        $mock_process_factory->method('getProcess')
            ->with('git -C /tmp/dummy_path log 1.0.0..1.0.1 --oneline')
            ->willReturn($mock_process);
        $retriever = new ChangelogRetriever($mock_retriever, $mock_process_factory);
        $fake_lock = (object) [
            'packages' => [
                $fake_package_data,
            ],
        ];
        $log = $retriever->retrieveChangelog('psr/log', $fake_lock, '1.0.0', '1.0.1');
        $markdown = $log->getAsMarkdown();
        $this->assertEquals('- [ababab](https://github.com/psr/log/commit/ababab) Change 1
', $markdown);
    }

    protected function getTestData()
    {
        $fake_package_data =  (object) [
            'name' => 'psr/log',
            'source' => (object) [
                'url' => 'https://github.com/psr/log',
            ]
        ];
        return $fake_package_data;
    }

    protected function getMockRetriever($fake_package_data)
    {
        $fake_path = '/tmp/dummy_path';
        $mock_retriever = $this->createMock(DependencyRepoRetriever::class);
        $mock_retriever->expects($this->once())
            ->method('retrieveDependencyRepo')
            ->with($fake_package_data)
            ->willReturn($fake_path);
        return $mock_retriever;
    }
}
