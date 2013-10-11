<?php

/**
 * FormController.
 */

/**
 * Form action.
 */
$app->get('/form', function() use ($app) {
    $featured = (bool) $app
        ->request
        ->get('featured');

    $images = $app
        ->dm
        ->getRepository('Image')
        ->findBy(['type' => null]);

    if ($featured) {
        $memes = $app
            ->dm
            ->getRepository('Meme')
            ->findBy(
                ['status' => Meme::STATUS_PUBLISHED],
                ['created_at' => -1]
            )
            ->limit(20);
    } else {
        $memes = [];
    }

    $app->render(
        'Form/form.html.twig',
        [
            'images'   => $images,
            'memes'    => $memes,
            'featured' => $featured
        ]
    );
});