<?php

/**
 * AdminController.php
 */

$app->get('/logmein', function() use ($app) {
    if ($app->secured)
        return $app->redirect('/');

    // Render
    $app->render(
        'Admin/logmein.html.twig',
        [
            'errir'  => false
        ]
    );
});

$app->post('/logmein', function() use ($app) {
    $token = $app
        ->request
        ->post('token');

    // Token good?
    if (SECURITY_TOKEN == $token) {
        $app->setCookie(SECURITY_COOKIE, $token, '+1 day', '/');
        return $app->redirect('/admin');
    }

    $app->render(
        'Admin/logmein.html.twig',
        [
            'error' => 1
        ]
    );
});

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