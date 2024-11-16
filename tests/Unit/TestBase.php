<?php

namespace Violinist\ChangelogFetcher\Tests\Unit;

use PHPUnit\Framework\TestCase;

abstract class TestBase extends TestCase
{
    protected function getTestData()
    {
        $fake_package_data =  (object) [
            'name' => 'psr/log',
            'source' => (object) [
                'type' => 'git',
                'url' => 'https://github.com/psr/log',
            ],
        ];
        return $fake_package_data;
    }
}
