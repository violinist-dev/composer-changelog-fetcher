<?php

namespace Violinist\ChangelogFetcher;

use Symfony\Component\Process\Process;
use Violinist\ProcessFactory\ProcessFactoryInterface;

class ProcessFactory implements ProcessFactoryInterface
{

    /**
     * Get a process instance.
     *
     * The function signature is the same as the symfony process command.
     *
     * @return \Symfony\Component\Process\Process
     */
    public function getProcess(array $command, ?string $cwd = null, ?array $env = null, $input = null, ?float $timeout = 60)
    {
        $process_class = Process::class;
        return new $process_class($command, $cwd, $env, $input, $timeout);
    }
}
