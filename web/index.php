<?php

require __DIR__ . '/../app/bootstrap.php';

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
$app
    ->view
    ->getInstance()
    ->addFunction(new Twig_SimpleFunction('asset', function($url) {
        if (CDN_ENABLED) {
            return CDN_URL . $url;
        } else {
            return $url;
        }
    }));

// Secure
$app->secured = function() use ($app) {
    // For I am admin!
    $ip = $app
        ->request
        ->getIp();
    if ('::1' == $ip)  {
        return true;
    }

    // Security cookie
    if (SECURITY_TOKEN == $app->getCookie(SECURITY_COOKIE)) {
        return true;
    }

    return false;
};

// Routes
$controllers = new DirectoryIterator(APP_ROOT . '/app/controllers');
foreach ($controllers as $controller) {
    if ($controller->isFile() && 'php' == $controller->getExtension()) {
        require $controller->getPathname();
    }
}

// Run for it!
$app->run();