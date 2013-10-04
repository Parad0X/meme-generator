<?php

/**
 * FormController.
 */

/**
 * Form action.
 */
$app->get('/form', function() use ($app) {
    $images = $app
        ->dm
        ->getRepository('Image')
        ->findBy(['type' => null]);

    $app->render(
        'Form/form.html.twig',
        [
            'images' => $images
        ]
    );
});