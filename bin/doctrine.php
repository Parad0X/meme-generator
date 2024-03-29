#!/usr/bin/env php
<?php

require __DIR__ . '/../app/bootstrap.php';

$helpers = array(
    'dm' => new Doctrine\ODM\MongoDB\Tools\Console\Helper\DocumentManagerHelper($app->dm),
);

$helperSet = isset($helperSet) ? $helperSet : new \Symfony\Component\Console\Helper\HelperSet();
foreach ($helpers as $name => $helper) {
    $helperSet->set($helper, $name);
}

$cli = new \Symfony\Component\Console\Application('Doctrine ODM MongoDB Command Line Interface', Doctrine\ODM\MongoDB\Version::VERSION);
$cli->setCatchExceptions(true);
$cli->setHelperSet($helperSet);
$cli->addCommands(array(
    new \Doctrine\ODM\MongoDB\Tools\Console\Command\QueryCommand(),
    new \Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateDocumentsCommand(),
    new \Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateRepositoriesCommand(),
    new \Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateProxiesCommand(),
    new \Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateHydratorsCommand(),
    new \Doctrine\ODM\MongoDB\Tools\Console\Command\ClearCache\MetadataCommand(),
    new \Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\CreateCommand(),
    new \Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\UpdateCommand(),
    new \Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\DropCommand(),
));
$cli->run();
