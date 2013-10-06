<?php

/**
 * AdminController.php
 */

//********************************************* ADMINS *********************************************//

if (! $app->secured) {
    return;
}

$app->get('/admin', function() use ($app) {
    // Original images
    $images = $app
        ->dm
        ->getRepository('Image')
        ->findBy(['type' => null]);

    // Memes requiring moderation
    $memes = $app
        ->dm
        ->getRepository('Meme')
        ->findBy(
            ['status' => Meme::STATUS_NEW],
            ['created_at' => 1]
        )
        ->limit(48);

    // Render
    $app->render(
        'Admin/index.html.twig',
        [
            'images' => $images,
            'memes'  => $memes
        ]
    );
});