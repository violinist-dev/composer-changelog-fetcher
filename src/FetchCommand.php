<?php

namespace Violinist\ChangelogFetcher;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Violinist\ComposerLockData\ComposerLockData;

class FetchCommand extends Command
{
    protected $retriever;

    protected static $defaultName = 'fetch';

    public function __construct(ChangelogRetriever $retriever)
    {
        $this->retriever = $retriever;
        parent::__construct();
    }

    public function configure()
    {
        $this->setDefinition(new InputDefinition([
            new InputOption('package', 'p', InputOption::VALUE_REQUIRED),
            new InputOption('version_from', 'f', InputOption::VALUE_REQUIRED),
            new InputOption('version_to', 't', InputOption::VALUE_REQUIRED),
            new InputOption('directory', 'd', InputOption::VALUE_OPTIONAL),
            new InputOption('output', 'o', InputOption::VALUE_OPTIONAL)
        ]));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('package') || !$input->getOption('version_from') || !$input->getOption('version_to')) {
            throw new \InvalidArgumentException('Please supply options for package, version_from and version_to');
        }
        $directory = getcwd();
        if ($input->getOption('directory')) {
            $directory = $input->getOption('directory');
        }
        // Now try to read the lock data.
        $lock_data = ComposerLockData::createFromFile("$directory/composer.lock");
        $log = $this->retriever->retrieveChangelog(
            $input->getOption('package'),
            $lock_data->getData(),
            $input->getOption('version_from'),
            $input->getOption('version_to')
        );
        switch ($input->getOption('output')) {
            case 'json':
                $output->writeln($log->getAsJson());
                break;

            default:
                $lines = json_decode($log->getAsJson());
                foreach ($lines as $line) {
                    $output->writeln(sprintf("%s: %s (%s)", $line->hash, $line->message, $line->link));
                }

        }
    }
}
