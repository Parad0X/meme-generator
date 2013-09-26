<?php

/**
 * AdminController.php
 */

$app->get('/admin', function() use ($app) {
    // Render
    $app->render('Admin/index.html.twig');
});