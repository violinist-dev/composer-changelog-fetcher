<?php

namespace Violinist\ChangelogFetcher\Tests\Unit;

use Symfony\Component\Process\Process;
use Violinist\ChangelogFetcher\ChangelogRetriever;
use Violinist\ChangelogFetcher\DependencyRepoRetriever;
use Violinist\ProcessFactory\ProcessFactoryInterface;

class ChangelogRetrieverTest extends TestBase
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
            ->with(['git', '-C', '/tmp/dummy_path', 'log', '1.0.0..1.0.1', '--oneline'])
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
            ->with(['git', '-C', '/tmp/dummy_path', 'log', '1.0.0..1.0.1', '--oneline'])
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
            ->with(['git', '-C', '/tmp/dummy_path', 'log', '1.0.0..1.0.1', '--oneline'])
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
            ->with(['git', '-C', '/tmp/dummy_path', 'log', '1.0.0..1.0.1', '--oneline'])
            ->willReturn($mock_process);
        $retriever = new ChangelogRetriever($mock_retriever, $mock_process_factory);
        $fake_lock = (object) [
            'packages' => [
                $fake_package_data,
            ],
        ];
        $log = $retriever->retrieveChangelog('psr/log', $fake_lock, '1.0.0', '1.0.1');
        $markdown = $log->getAsMarkdown();
        $this->assertEquals('- [ababab](https://github.com/psr/log/commit/ababab) `Change 1`
', $markdown);
    }

    public function testRetrieveChangelogAndChangedFiles()
    {
        $fake_package_data = $this->getTestData();
        $fake_package_data->source->url = 'git@github.com:psr/log';
        $mock_retriever = $this->getMockRetriever($fake_package_data);
        $mock_process1 = $this->createMock(Process::class);
        $mock_process1->method('getOutput')
            ->willReturn('ababab Change 1');
        $mock_process2 = $this->createMock(Process::class);
        $mock_process2->method('getOutput')
            ->willReturn("File1\n\nFileOtherFile.php\n\nAnotherFileWeirdExtensionHuh.weird\n");
        $mock_process_factory = $this->createMock(ProcessFactoryInterface::class);
        $mock_process_factory->method('getProcess')
            ->willReturnCallback(function (array $command) use ($mock_process1, $mock_process2) {
                if ($command === ['git', '-C', '/tmp/dummy_path', 'log', '1.0.0..1.0.1', '--oneline']) {
                    return $mock_process1;
                }
                return $mock_process2;
            });
        $retriever = new ChangelogRetriever($mock_retriever, $mock_process_factory);
        $fake_lock = (object) [
            'packages' => [
                $fake_package_data,
            ],
        ];
        $changes = $retriever->retrieveChangelogAndChangedFiles('psr/log', $fake_lock, '1.0.0', '1.0.1');
        self::assertEquals(['File1', 'FileOtherFile.php', 'AnotherFileWeirdExtensionHuh.weird'], $changes->getChangedFiles());
        $log = $changes->getChangelog();
        $markdown = $log->getAsMarkdown();
        self::assertEquals('- [ababab](https://github.com/psr/log/commit/ababab) `Change 1`
', $markdown);
    }

    public function testRetrieveTags()
    {
      $fake_package_data = $this->getTestData();
      $mock_retriever = $this->getMockRetriever($fake_package_data);
      $mock_process = $this->createMock(Process::class);
      $mock_process->method('getOutput')
        ->willReturn(file_get_contents(__DIR__ . '/../assets/psrlog-example.txt'));
      $mock_process_factory = $this->createMock(ProcessFactoryInterface::class);
      $mock_process_factory->method('getProcess')
        ->willReturn($mock_process);
      $retriever = new ChangelogRetriever($mock_retriever, $mock_process_factory);
      $fake_lock = (object) [
        'packages' => [
          $fake_package_data,
        ],
      ];
      $tags = $retriever->retrieveTagsBetweenShas($fake_lock, 'psr/log', 'd49695b909c3b7628b6289db5479a1c204601f11', 'bf73deb2b3b896a9d9c75f3f0d88185d2faa27e2');
      self::assertEquals(['1.1.4', '1.1.3', '1.1.2'], $tags);
    }

    protected function getMockRetriever($fake_package_data)
    {
        $fake_path = '/tmp/dummy_path';
        $mock_retriever = $this->createMock(DependencyRepoRetriever::class);
        $mock_retriever->method('retrieveDependencyRepo')
            ->with($fake_package_data)
            ->willReturn($fake_path);
        return $mock_retriever;
    }
}
