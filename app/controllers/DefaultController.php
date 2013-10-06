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
        ->findBy(
            ['status' => Meme::STATUS_PUBLISHED],
            ['created_at' => -1]
        )
        ->limit(20);

    if ($page > 1) {
        $memes->skip(20 * ($page - 1));
    }

    // View
    $app->render(
        'Default/index.html.twig',
        [
            'memes' => $memes
        ]
    );
});