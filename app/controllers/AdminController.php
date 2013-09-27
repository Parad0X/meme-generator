<?php

/**
 * AdminController.php
 */

$app->get('/admin', function() use ($app) {
    $images = $app
        ->dm
        ->getRepository('Image')
        ->findBy(['type' => null]);

    // Render
    $app->render(
        'Admin/index.html.twig',
        [
            'images' => $images
        ]
    );
});