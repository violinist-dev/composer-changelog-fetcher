<?php

namespace Violinist\ChangelogFetcher\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Violinist\ChangelogFetcher\ProcessFactory;

class ProcessFactoryTest extends TestCase
{
    public function testFactory()
    {
        $processFactory = new ProcessFactory();
        $this->assertEquals(Process::class, get_class($processFactory->getProcess(['true'])));
    }
}
