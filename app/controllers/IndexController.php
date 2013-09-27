<?php

/**
 * IndexController.
 */

/**
 * Index action.
 */
$app->get('/', function() use ($app) {
    $page = (int) $app->request->get('page');

    // Find memes and images
    $memes = $app
        ->dm
        ->getRepository('Meme')
        ->findBy([], ['created_at' => -1])
        ->limit(20);

    if ($page > 1) {
        $memes->skip(20 * ($page - 1));
    }

    $images = $app
        ->dm
        ->getRepository('Image')
        ->findBy(['type' => null]);

    // View
    $app->render(
        'Index/index.html.twig',
        [
            'memes'  => $memes,
            'images' => $images
        ]
    );
});