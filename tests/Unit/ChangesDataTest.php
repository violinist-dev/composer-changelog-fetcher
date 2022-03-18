<?php

namespace Violinist\ChangelogFetcher\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Violinist\ChangelogFetcher\ChangesData;
use Violinist\GitLogFormat\ChangeLogData;

class ChangesDataTest extends TestCase
{

    public function testMethods()
    {
        $changelog = new ChangeLogData();
        $data = new ChangesData($changelog, ['a_file']);
        self::assertEquals(['a_file'], $data->getChangedFiles());
        // Try a couple setters.
        $data->setChangedFiles(['another_file']);
        self::assertEquals(['another_file'], $data->getChangedFiles());
        $c_data = ChangeLogData::createFromString("ababab change 1\nfefefe change 2");
        $data->setChangelog($c_data);
        self::assertEquals($c_data, $data->getChangelog());
    }
}
