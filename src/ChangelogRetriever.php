<?php

namespace Violinist\ChangelogFetcher;

use Violinist\ComposerLockData\ComposerLockData;
use Violinist\GitLogFormat\ChangeLogData;
use Violinist\ProcessFactory\ProcessFactoryInterface;

use function peterpostmann\uri\parse_uri;

class ChangelogRetriever
{
    private $authToken;

    /**
     * Dependency retriever.
     *
     * @var DependencyRepoRetriever
     */
    protected $retriever;

    /**
     * Process factory.
     *
     * @var ProcessFactory
     */
    protected $processFactory;

    public function setAuthToken($token)
    {
        $this->authToken = $token;
    }

    public function __construct(DependencyRepoRetriever $retriever, ProcessFactoryInterface $processFactory)
    {
        $this->retriever = $retriever;
        $this->processFactory = $processFactory;
    }

    /**
     * @return ChangeLogData
     *
     * @throws \Exception
     */
    public function retrieveChangelog($package_name, $lockdata, $version_from, $version_to)
    {
        $lock_data_obj = new ComposerLockData();
        $lock_data_obj->setData($lockdata);
        $data = $lock_data_obj->getPackageData($package_name);
        $clone_path = $this->retrieveDependencyRepo($data);
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
