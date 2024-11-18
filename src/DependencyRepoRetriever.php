<?php

namespace Violinist\ChangelogFetcher;

use Violinist\ProcessFactory\ProcessFactoryInterface;

use Violinist\RepoAndTokenToCloneUrl\ToCloneUrl;

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
        $clone_urls_to_try = [
            $repo_path,
        ];
        if (!empty($this->authToken)) {
            $clone_urls_to_try[] = ToCloneUrl::fromRepoAndToken($repo_path, $this->authToken);
            // Make sure we don't do the same twice though.
            $clone_urls_to_try = array_unique($clone_urls_to_try);
        }
        foreach ($clone_urls_to_try as $clone_path_to_try) {
            try {
                return $this->cloneOrPull($clone_path, $clone_path_to_try);
            } catch (\Exception $e) {
                // Just try the next one.
            }
        }
        throw new \Exception('Could not clone or pull the repo');
    }

    protected function cloneOrPull($clone_path, $repo_path)
    {
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
