<?php

namespace Violinist\ChangelogFetcher;

use Violinist\ComposerLockData\ComposerLockData;
use Violinist\GitLogFormat\ChangeLogData;
use Violinist\ProcessFactory\ProcessFactoryInterface;

use function peterpostmann\uri\parse_uri;

class ChangelogRetriever
{

    /**
     * Dependency retriever.
     *
     * @var DependencyRepoRetriever
     */
    protected $retriever;

    /**
     * Process factory.
     *
     * @var ProcessFactoryInterface
     */
    protected $processFactory;

    public function __construct(DependencyRepoRetriever $retriever, ProcessFactoryInterface $processFactory)
    {
        $this->retriever = $retriever;
        $this->processFactory = $processFactory;
    }

    public function retrieveChangelogAndChangedFiles($package_name, $lockdata, $version_from, $version_to) : ChangesData
    {
        $changelog = $this->retrieveChangelog($package_name, $lockdata, $version_from, $version_to);
        $changed_files = $this->retrieveChangedFiles($package_name, $lockdata, $version_from, $version_to);
        $changes = new ChangesData($changelog, $changed_files);
        return $changes;
    }

    public function retrieveChangedFiles($package_name, $lockdata, $version_from, $version_to) : array
    {
        $clone_path = $this->getClonePathAndRetrieveRepo($lockdata, $package_name);
        $files_raw_command = sprintf('git -C %s diff --name-only %s %s', $clone_path, $version_from, $version_to);
        $process = $this->processFactory->getProcess($files_raw_command);
        $process->run();
        if ($process->getExitCode()) {
            throw new \Exception('git diff process exited with wrong exit code. Exit code was: ' . $process->getExitCode());
        }
        $string = $process->getOutput();
        $files = [];
        foreach (explode("\n", $string) as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            $files[] = $line;
        }
        return $files;
    }

    protected function getClonePathAndRetrieveRepo($lockdata, $package_name)
    {
        $data = $this->getPackageLockData($lockdata, $package_name);
        return $this->retrieveDependencyRepo($data);
    }

    protected function getPackageLockData($lockdata, $package_name)
    {
        $lock_data_obj = new ComposerLockData();
        $lock_data_obj->setData($lockdata);
        return $lock_data_obj->getPackageData($package_name);
    }

    /**
     * @return ChangeLogData
     *
     * @throws \Exception
     */
    public function retrieveChangelog($package_name, $lockdata, $version_from, $version_to) : ChangeLogData
    {
        $data = $this->getPackageLockData($lockdata, $package_name);
        $clone_path = $this->getClonePathAndRetrieveRepo($lockdata, $package_name);
        // Then try to get the changelog.
        $command = sprintf('git -C %s log %s..%s --oneline', $clone_path, $version_from, $version_to);
        $process = $this->processFactory->getProcess($command);
        $process->run();
        if ($process->getExitCode()) {
            throw new \Exception('git log process exited with wrong exit code. Exit code was: ' . $process->getExitCode());
        }
        $changelog_string = $process->getOutput();
        if (empty($changelog_string)) {
            throw new \Exception('The changelog string was empty for package ' . $package_name);
        }
        // Then split it into lines that makes sense.
        $log = ChangeLogData::createFromString($changelog_string);
        // Then assemble the git source.
        $git_url = preg_replace('/.git$/', '', $data->source->url);
        $repo_parsed = parse_uri($git_url);
        if (!empty($repo_parsed)) {
            switch ($repo_parsed['_protocol']) {
                case 'git@github.com':
                    $git_url = sprintf('https://github.com/%s', $repo_parsed['path']);
                    break;
            }
        }
        $log->setGitSource($git_url);
        return $log;
    }

    protected function retrieveDependencyRepo($data)
    {
        return $this->retriever->retrieveDependencyRepo($data);
    }
}
