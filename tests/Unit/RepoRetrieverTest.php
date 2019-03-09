<?php

namespace Violinist\ChangelogFetcher\Tests\Unit;

use Symfony\Component\Process\Process;
use Violinist\ChangelogFetcher\DependencyRepoRetriever;
use Violinist\ProcessFactory\ProcessFactoryInterface;

class RepoRetrieverTest extends TestBase
{
    public function testRetrieveNoSource()
    {
        $mock_factory = $this->createMock(ProcessFactoryInterface::class);
        $retriever = new DependencyRepoRetriever($mock_factory);
        $mock_data = $this->getTestData();
        $this->expectExceptionMessage('Unknown source or non-git source found for psr/log. Aborting.');
        unset($mock_data->source);
        $retriever->retrieveDependencyRepo($mock_data);
    }

    public function testBadExitCode()
    {
        $mock_process = $this->createMock(Process::class);
        $mock_process->method('getExitCode')
            ->willReturn(1);
        $mock_factory = $this->createMock(ProcessFactoryInterface::class);
        $mock_factory->method('getProcess')
            ->willReturn($mock_process);
        $data = $this->getTestData();
        $this->expectExceptionMessage('Wrong exit code from retrieving git repo: 1');
        $retriever = new DependencyRepoRetriever($mock_factory);
        $retriever->retrieveDependencyRepo($data);
    }

    public function testClone()
    {
        $mock_process = $this->createMock(Process::class);
        $mock_factory = $this->createMock(ProcessFactoryInterface::class);
        $path = '/tmp/e9a8b66d7a4bac57a08b8f0f2664c50f';
        $mock_factory->expects($this->once())
            ->method('getProcess')
            ->with('git clone https://github.com/psr/log ' . $path)
            ->willReturn($mock_process);
        $data = $this->getTestData();
        $retriever = new DependencyRepoRetriever($mock_factory);
        $this->assertEquals($path, $retriever->retrieveDependencyRepo($data));
    }
}
