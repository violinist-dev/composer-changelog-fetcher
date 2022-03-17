<?php

namespace Violinist\ChangelogFetcher;

class ChangesData
{
    private $changelog;
    private $changedFiles;

    public function __construct($changelog, $changedFiles)
    {
        $this->changelog = $changelog;
        $this->changedFiles = $changedFiles;
    }

    /**
     * @return mixed
     */
    public function getChangelog()
    {
        return $this->changelog;
    }

    /**
     * @param mixed $changelog
     */
    public function setChangelog($changelog)
    {
        $this->changelog = $changelog;
    }

    /**
     * @return mixed
     */
    public function getChangedFiles()
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
