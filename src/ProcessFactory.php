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
    public function getProcess($commandline, $cwd = null, array $env = null, $input = null, $timeout = 60, array $options = null)
    {
        $process_class = Process::class;
        return new $process_class($commandline, $cwd, $env, $input, $timeout, $options);
    }
}
