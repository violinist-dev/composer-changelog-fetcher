<?php

namespace Violinist\ChangelogFetcher;

use Symfony\Component\Process\Process;
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

    public function retrieveTagsBetweenShas($lockdata, $package_name, $sha1, $sha2) : array
    {
        $clone_path = $this->getClonePathAndRetrieveRepo($lockdata, $package_name);
        $command = [
            'git',
            '-C',
            $clone_path,
            'log',
            sprintf('%s...%s', $sha1, $sha2),
            '--decorate', '--simplify-by-decoration',
        ];
        $process = $this->processFactory->getProcess($command);
        $process->run();
        if ($process->getExitCode()) {
            throw new \Exception('No tags found for the range');
        }

        $output = $process->getOutput();
        // OK, so filter all lines that contain something like "tag: v1.2.3". Or
        // mimick what would be a pipe to grep like this:
        // | grep -o 'tag: [^,)]\+'
        $useful_array = array_values(array_filter(explode("\n", $output), function ($line) {
            return preg_match('/tag: [^,)]/', $line);
        }));
        // Now we have the lines, now we just need to filter out the tag parts
        // of it. This mimics doing some pipe to sed thing we used to have.
        $actual_tags = array_map(function ($line) {
            // At this point, the string will either look something like this:
            // commit 5f0f17732e88efcd15d2554d4d4c2df4e380e65f (tag: v3.3.1, origin/3.3)
            // or like this:
            // commit e26ee41a73d1ae3adbd3c06eaf039ac3c1dfcc57 (tag: 3.3.0)
            // or variations of that.
            $tag_line_matches = [];
            preg_match('/tag: .*[,)]/', $line, $tag_line_matches);
            // Now, remove the string "tag: "
            if (empty($tag_line_matches[0])) {
                return null;
            }
            $without_tag = str_replace('tag: ', '', $tag_line_matches[0]);
            $only_actual_tag = preg_replace('/\)?|,.*/', '', $without_tag);
            return $only_actual_tag;
        }, $useful_array);
        return array_filter($actual_tags);
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
        $files_raw_command = ['git', '-C', $clone_path, 'diff', '--name-only', $version_from, $version_to];
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
        $command = ['git', '-C', $clone_path, 'log', sprintf('%s..%s', $version_from, $version_to), '--oneline'];
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
