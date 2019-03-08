<?php
// Copied this from drush/drush.

use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Violinist\ChangelogFetcher\ChangelogRetriever;
use Violinist\ChangelogFetcher\FetchCommand;
use Violinist\ChangelogFetcher\ProcessFactory;

$cwd = isset($_SERVER['PWD']) && is_dir($_SERVER['PWD']) ? $_SERVER['PWD'] : getcwd();
// Set up autoloader
$loader = false;
if (file_exists($autoloadFile = __DIR__ . '/vendor/autoload.php')
    || file_exists($autoloadFile = __DIR__ . '/../autoload.php')
    || file_exists($autoloadFile = __DIR__ . '/../../autoload.php')
) {
    $loader = include_once($autoloadFile);
} else {
    throw new \Exception("Could not locate autoload.php. cwd is $cwd; __DIR__ is " . __DIR__);
}


$app = new Application();
$container = new ContainerBuilder();

$container->register('process_factory', ProcessFactory::class);

$container->register('changelog_retriever', ChangelogRetriever::class)
    ->addArgument(new Reference('dependency_repo_retriever'))
    ->addArgument(new Reference('process_factory'));
$container->register('dependency_repo_retriever', \Violinist\ChangelogFetcher\DependencyRepoRetriever::class)
    ->addArgument(new Reference('process_factory'));

$app->add(new FetchCommand($container->get('changelog_retriever')));

$app->setName('changelog-fetcher');

$app->run();
