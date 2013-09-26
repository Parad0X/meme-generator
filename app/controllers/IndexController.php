<?php

/**
 * IndexController.
 */

/**
 * Index action.
 */
$app->get('/', function() use ($app) {
    $app->render('Index/index.html.twig');
});