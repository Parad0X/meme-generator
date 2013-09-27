<?php

/**
 * MemesController.
 */

use mg\ImageTools;

/**
 * Create new meme.
 */
$app->post('/memes', function() use ($app) {
    $request    = $app->request;
    $imageId    = $request->post('image');
    $textTop    = $request->post('text_top');
    $textBottom = $request->post('text_bottom');

    // Find image
    $image = $app
        ->dm
        ->getRepository('Image')
        ->find($imageId);

    if (! $image) {
        return render_json([
            'status' => 'error',
            'data'   => 'Image not found'
        ], 400);
    }

    // Validate text
    if (! $textTop && ! $textBottom) {
        return render_json([
            'status' => 'error',
            'data'   => 'Top and/or bottom text required'
        ], 400);
    }

    // Create meme image and store it
    ob_start();
        ImageTools::createMeme($image->file->getBytes(), $textTop, $textBottom);
    $memeImageData = ob_get_clean();

    // Store it in a temp file
    $tempFile = tempnam(sys_get_temp_dir(), 'meme');
    file_put_contents($tempFile, $memeImageData);

    // Save meme image
    $memeImage = new Image($tempFile, $image->width, $image->height, 'meme');
    $app->dm->persist($memeImage);

    // Save Meme
    $meme = new Meme($memeImage, $textTop, $textBottom);
    $app->dm->persist($meme);

    // Flush the tanks!
    $app->dm->flush();

    // Cleanup
    unlink($tempFile);

    // Redirect
    $app->redirect('/memes/' . $meme->id);
});

/**
 * Show meme.
 */
$app->get('/memes/:id', function($id) use ($app) {
    $meme = $app
        ->dm
        ->getRepository('Meme')
        ->find($id);

    if (! $meme) {
        $app->pass();
    }

    return $app->render(
        'Memes/show.html.twig',
        [
            'meme' => $meme
        ]
    );
});