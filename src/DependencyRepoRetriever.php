<?php

namespace Violinist\ChangelogFetcher;

use Violinist\ProcessFactory\ProcessFactoryInterface;

use function peterpostmann\uri\parse_uri;

class DependencyRepoRetriever
{
    protected $processFactory;

    protected $authToken;

    public function __construct(ProcessFactoryInterface $processFactory)
    {
        $this->processFactory = $processFactory;
    }

    public function setAuthToken($token)
    {
        $this->authToken = $token;
    }

    public function retrieveDependencyRepo($data)
    {
        // First find the repo source.
        if (!isset($data->source) || $data->source->type != 'git') {
            throw new \Exception(sprintf('Unknown source or non-git source found for %s. Aborting.', $data->name));
        }
        if (empty($data->name)) {
            throw new \Exception('No package name found');
        }
        // We could have this cached in the md5 of the package name.
        $clone_path = '/tmp/' . md5($data->name);
        $repo_path = $data->source->url;
        $repo_parsed = parse_uri($repo_path);
        if (!empty($repo_parsed)) {
            switch ($repo_parsed['_protocol']) {
                case 'git@github.com':
                    $repo_path = sprintf(
                        'https://x-access-token:%s@github.com/%s',
                        $this->authToken,
                        $repo_parsed['path']
                    );
                    break;
            }
        }
        if (!file_exists($clone_path)) {
            $command = ['git', 'clone', $repo_path, $clone_path];
        } else {
            $command = ['git', '-C', $clone_path, 'pull'];
        }
        $process = $this->processFactory->getProcess($command);
        $process->run();
        if ($process->getExitCode()) {
            throw new \Exception('Wrong exit code from retrieving git repo: ' . $process->getExitCode());
        }
        return $clone_path;
    }
}
