<?php

date_default_timezone_set('UTC');

/**
 * Constants.
 */
define('APP_ROOT',       realpath(__DIR__ . '/..'));
define('CACHE_DIR',      APP_ROOT . '/app/cache');
define('APP_URL',        'http://twd.parad0x.me/');
define('SECURITY_TOKEN', 'a33350c7a5b59d7b1eb3aed7286948b2');
define('SECURITY_COOKIE', 'twd-auth-cookie-poo');

require APP_ROOT . '/vendor/autoload.php';

// App
$app = new \Slim\Slim([
    'templates.path' => APP_ROOT . '/app/templates',
    'log.enabled'    => true,
    'log.writer'     => new \Slim\Extras\Log\DateTimeFileWriter(array(
        'path'        => APP_ROOT . '/app/logs',
        'name_format' => 'Ymd'
    ))
]);
$app->configureMode('development', function() use ($app) {
    $app->config('debug', true);
    $app->log->setLevel(\Slim\Log::DEBUG);
});
$app->configureMode('production', function() use ($app) {
    $app->config('debug', false);
    $app->log->setLevel(\Slim\Log::ERROR);
});

// DI
$app
    ->container
    ->singleton('dm', function() use ($app) {
        // Load annotations
        require APP_ROOT . '/vendor/doctrine/mongodb-odm/lib/Doctrine/ODM/MongoDB/Mapping/Annotations/DoctrineAnnotations.php';

        // Connection
        if ($app->getMode() == 'production') {
            $conn = new Doctrine\MongoDB\Connection('127.0.0.1');
        } else {
            $conn = new Doctrine\MongoDB\Connection('192.168.33.10');
        }

        // Configuration
        $config = new Doctrine\ODM\MongoDB\Configuration();
        $config->setDefaultDB('meme_generator');
        $config->setProxyDir(CACHE_DIR . '/doctrine/proxies');
        $config->setProxyNamespace('MgProxies');
        $config->setAutoGenerateProxyClasses(true);
        $config->setHydratorDir(CACHE_DIR . '/doctrine/hydrators');
        $config->setHydratorNamespace('MgHydrators');
        $config->setAutoGenerateHydratorClasses(true);
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(APP_ROOT . '/app/models'));

        // EVM
        $evm = new Doctrine\Common\EventManager();

        return Doctrine\ODM\MongoDB\DocumentManager::create($conn, $config, $evm);
    });
