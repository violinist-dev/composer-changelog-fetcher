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
        $fake_package_data =  (object) [
            'name' => 'psr/log',
            'source' => (object) [
                'url' => 'https://gihub.com/psr/log',
            ]
        ];
        $fake_path = '/tmp/dummy_path';
        $mock_retriever = $this->createMock(DependencyRepoRetriever::class);
        $mock_retriever->expects($this->once())
            ->method('retrieveDependencyRepo')
            ->with($fake_package_data)
            ->willReturn($fake_path);
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
        $this->assertEquals('[{"hash":"ababab","message":"Change 1"},{"hash":"fefefe","message":"Change 2"}]', $log->getAsJson());
    }
}
