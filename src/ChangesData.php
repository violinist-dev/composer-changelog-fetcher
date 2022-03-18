<?php

namespace Violinist\ChangelogFetcher;

use Violinist\GitLogFormat\ChangeLogData;

class ChangesData
{
    /**
     * Changelog data.
     *
     * @var ChangeLogData
     */
    private $changelog;

    /**
     * Changed files.
     *
     * @var array
     */
    private $changedFiles;

    public function __construct(ChangeLogData $changelog, array $changedFiles)
    {
        $this->changelog = $changelog;
        $this->changedFiles = $changedFiles;
    }

    /**
     * @return ChangeLogData
     */
    public function getChangelog() : ChangeLogData
    {
        return $this->changelog;
    }

    /**
     * @param mixed $changelog
     */
    public function setChangelog(ChangeLogData $changelog)
    {
        $this->changelog = $changelog;
    }

    /**
     * @return array
     */
    public function getChangedFiles() : array
    {
        return $this->changedFiles;
    }

    /**
     * @param mixed $changedFiles
     */
    public function setChangedFiles($changedFiles)
    {
        $this->changedFiles = $changedFiles;
    }
}
