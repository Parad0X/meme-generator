<?php

/**
 * TestController.
 */

if (! $app->secured) {
    return;
}

/**
 * Test action.
 */
$app->get('/test', function() use ($app) {
    $app->render('Test/test.html.twig');
});