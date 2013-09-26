<?php

date_default_timezone_set('UTC');

/**
 * Constants.
 */
define('APP_ROOT', realpath(__DIR__ . '/..'));
define('CACHE_DIR', APP_ROOT . '/app/cache');

require APP_ROOT . '/vendor/autoload.php';

// Global functions
function render_json($data, $status = 200) {
    global $app;

    $app
        ->response
        ->setStatus($status);

    $app
        ->response
        ->headers
        ->set('Content-Type', 'application/json');

    echo json_encode($data);
}

// App
$app = new \Slim\Slim([
    'templates.path' => APP_ROOT . '/app/templates',
    'debug'          => true,
    'log.level'      => \Slim\Log::DEBUG,
    'log.enabled'    => true,
    'log.writer'     => new \Slim\Extras\Log\DateTimeFileWriter(array(
        'path'        => APP_ROOT . '/app/logs',
        'name_format' => 'Ymd'
    ))
]);

// DI
$app
    ->container
    ->singleton('dm', function() {
        // Load annotations
        require APP_ROOT . '/vendor/doctrine/mongodb-odm/lib/Doctrine/ODM/MongoDB/Mapping/Annotations/DoctrineAnnotations.php';

        // Connection
        $conn = new Doctrine\MongoDB\Connection('192.168.33.10');

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
