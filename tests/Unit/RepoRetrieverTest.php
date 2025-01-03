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
        $this->expectExceptionMessage('Could not clone or pull the repo');
        $retriever = new DependencyRepoRetriever($mock_factory);
        $retriever->retrieveDependencyRepo($data);
    }

    /**
     * @dataProvider providerTestClone
     */
    public function testClone(string $expected_path, string $repo_path, $data, $token = 'dummy')
    {
        $mock_process = $this->createMock(Process::class);
        $mock_process->method('getExitCode')
            // We are only actually returning the process if the command array
            // is correct. So we can safely return 0 here.
            ->willReturn(0);
        $mock_factory = $this->createMock(ProcessFactoryInterface::class);
        $mock_factory->method('getProcess')
            ->with(['git', 'clone', $repo_path, $expected_path])
            ->willReturn($mock_process);
        $retriever = new DependencyRepoRetriever($mock_factory);
        $retriever->setAuthToken($token);
        $this->assertEquals($expected_path, $retriever->retrieveDependencyRepo($data));
    }

    public static function providerTestClone() : array
    {
        return [
            [
                '/tmp/e9a8b66d7a4bac57a08b8f0f2664c50f',
                'https://github.com/psr/log',
                (object) [
                    'name' => 'psr/log',
                    'source' => (object) [
                        'type' => 'git',
                        'url' => 'https://github.com/psr/log',
                    ],
                ],
                null,
            ],
            [
                '/tmp/e9a8b66d7a4bac57a08b8f0f2664c50f',
                'https://x-access-token:dummy@github.com/psr/log',
                (object) [
                    'name' => 'psr/log',
                    'source' => (object) [
                        'type' => 'git',
                        'url' => 'https://github.com/psr/log',
                    ],
                ],
            ],
            [
                '/tmp/e9a8b66d7a4bac57a08b8f0f2664c50f',
                'https://www.github.com/psr/log',
                (object) [
                    'name' => 'psr/log',
                    'source' => (object) [
                        'type' => 'git',
                        'url' => 'https://www.github.com/psr/log',
                    ],
                ],
                null,
            ],
            [
                '/tmp/e9a8b66d7a4bac57a08b8f0f2664c50f',
                'https://x-access-token:dummy@github.com/psr/log',
                (object) [
                    'name' => 'psr/log',
                    'source' => (object) [
                        'type' => 'git',
                        'url' => 'https://www.github.com/psr/log',
                    ],
                ],
            ],
            [
                '/tmp/6ff4ca3539dc55131d6ca6fded5d2f0e',
                'https://x-access-token:dummy@github.com/user/private',
                (object) [
                    'name' => 'user/private',
                    'source' => (object) [
                        'type' => 'git',
                        'url' => 'git@github.com:user/private',
                    ],
                ],
            ],
            [
                '/tmp/6ff4ca3539dc55131d6ca6fded5d2f0e',
                'https://x-token-auth:dummy@bitbucket.org/user/private.git',
                (object) [
                    'name' => 'user/private',
                    'source' => (object) [
                        'type' => 'git',
                        'url' => 'git@bitbucket.org:user/private',
                    ],
                ],
            ],
            [
                '/tmp/6ff4ca3539dc55131d6ca6fded5d2f0e',
                'https://oauth2:dummy@gitlab.com/user/private',
                (object) [
                    'name' => 'user/private',
                    'source' => (object) [
                        'type' => 'git',
                        'url' => 'https://gitlab.com/user/private',
                    ],
                ],
            ],
            [
                '/tmp/6ff4ca3539dc55131d6ca6fded5d2f0e',
                'https://oauth2:dummy@gitlab.com/user/private',
                (object) [
                    'name' => 'user/private',
                    'source' => (object) [
                        'type' => 'git',
                        'url' => 'https://www.gitlab.com/user/private',
                    ],
                ],
            ],
            [
                '/tmp/6ff4ca3539dc55131d6ca6fded5d2f0e',
                'https://x-token-auth:dummy@bitbucket.org/user2/private2.git',
                (object) [
                    'name' => 'user/private',
                    'source' => (object) [
                        'type' => 'git',
                        'url' => 'https://bitbucket.org/user2/private2',
                    ],
                ],
            ],
            [
                '/tmp/6ff4ca3539dc55131d6ca6fded5d2f0e',
                'https://x-token-auth:dummy@bitbucket.org/user2/private2.git',
                (object) [
                    'name' => 'user/private',
                    'source' => (object) [
                        'type' => 'git',
                        'url' => 'https://www.bitbucket.org/user2/private2',
                    ],
                ],
            ],
            [
                '/tmp/6ff4ca3539dc55131d6ca6fded5d2f0e',
                'https://user:token@bitbucket.org/user2/private2.git',
                (object) [
                    'name' => 'user/private',
                    'source' => (object) [
                        'type' => 'git',
                        'url' => 'https://www.bitbucket.org/user2/private2',
                    ],
                ],
                'user:token',
            ],
            [
                '/tmp/6ff4ca3539dc55131d6ca6fded5d2f0e',
                'https://oauth2:dummy@gitlab.acme.com/user2/private2',
                (object) [
                    'name' => 'user/private',
                    'source' => (object) [
                        'type' => 'git',
                        'url' => 'https://gitlab.acme.com/user2/private2',
                    ],
                ],
            ],
            [
                '/tmp/6ff4ca3539dc55131d6ca6fded5d2f0e',
                'http://oauth2:dummy@gitlab.acme.com/user2/private2',
                (object) [
                    'name' => 'user/private',
                    'source' => (object) [
                        'type' => 'git',
                        'url' => 'http://gitlab.acme.com/user2/private2',
                    ],
                ],
            ],
            [
                '/tmp/6ff4ca3539dc55131d6ca6fded5d2f0e',
                'http://oauth2:dummy@gitlab.acme.com:9982/user2/private2',
                (object) [
                    'name' => 'user/private',
                    'source' => (object) [
                        'type' => 'git',
                        'url' => 'http://gitlab.acme.com:9982/user2/private2',
                    ],
                ],
            ],
            [
                '/tmp/6ff4ca3539dc55131d6ca6fded5d2f0e',
                'https://oauth2:dummy@gitlab.acme.com:2235/user2/private2',
                (object) [
                    'name' => 'user/private',
                    'source' => (object) [
                        'type' => 'git',
                        'url' => 'https://gitlab.acme.com:2235/user2/private2',
                    ],
                ],
            ],
        ];
    }
}
