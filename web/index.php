<?php

require __DIR__ . '/../app/bootstrap.php';

// Views
$app
    ->view(new \Slim\Views\Twig())
    ->parserOptions = [
        'charset'          => 'utf-8',
        'cache'            => CACHE_DIR . '/twig',
        'auto_reload'      => true,
        'strict_variables' => false,
        'autoescape'       => true
    ];
$app->view->parserExtensions = array(new \Slim\Views\TwigExtension());

// Routes
$controllers = new DirectoryIterator(APP_ROOT . '/app/controllers');
foreach ($controllers as $controller) {
    if ($controller->isFile() && 'php' == $controller->getExtension()) {
        require $controller->getPathname();
    }
}

// Run for it!
$app->run();