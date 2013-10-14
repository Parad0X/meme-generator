<?php

use Alex\Pagination\Pager;
use mg\Pagination\Adapter\MongoCursorAdapter;

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
    $page = (int) $app
        ->request
        ->get('page');

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
        );

    // Pagination
    $memes = (new Pager(new MongoCursorAdapter($memes)))
        ->setPerPage(40)
        ->setPage($page);

    // Render
    $app->render(
        'Admin/index.html.twig',
        [
            'images' => $images,
            'memes'  => $memes,
            'page'   => $page
        ]
    );
});