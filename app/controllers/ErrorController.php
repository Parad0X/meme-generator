<?php

// Use built in error handler not in prod
if ('production' != $app->getMode()) {
    return;
}

// 404
$app->notFound(function() use ($app) {
    $app->render('Error/404.html.twig');
});

// 503
$app->error(function() use ($app) {
    $app->render('Error/503.html.twig');
});