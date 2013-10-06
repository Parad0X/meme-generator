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
        render_json([
            'status'  => 'error',
            'message' => 'Image not found.'
        ]);
    }

    $textTop    = trim($textTop);
    $textBottom = trim($textBottom);

    // Validate text
    if (! $textTop && ! $textBottom) {
        render_json([
            'status'  => 'error',
            'message' => 'Top and/or bottom text is required.'
        ], 400);
    }

    // Create meme image ...
    ob_start();
        ImageTools::createMeme($image->file->getBytes(), $textTop, $textBottom);
    $memeImageData = ob_get_clean();

    // ... store it in a temp file
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
    render_json([
        'status'   => 'success',
        'redirect' => '/memes/' . $meme->id
    ]);
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

/**
 * Meme preview.
 */
$app->get('/memes/preview', function() use ($app) {
    $request    = $app->request;
    $imageId    = $request->get('image');
    $textTop    = $request->get('text_top');
    $textBottom = $request->get('text_bottom');
    $adj        = $request->get('adj');
    $width      = $request->get('width');
    $height     = $request->get('height');

    // No spaces please
    $textTop    = trim($textTop);
    $textBottom = trim($textBottom);

    // Find image
    $image = $app
        ->dm
        ->getRepository('Image')
        ->find($imageId);

    if (! $image) {
        return $app->pass();
    }

    $bytes = $image->file->getBytes();

    // Detect mime/type
    $mimeType = (new finfo())->buffer($bytes);

    // It's an image
    $app
        ->response
        ->headers
        ->set('Content-Type', $mimeType);

    if ($textTop || $textBottom) {
        ob_start();
            ImageTools::createMeme($bytes, $textTop, $textBottom, 60);
        $bytes = ob_get_clean();
    } else {
        $app->lastModified($image->uploadDate->getTimestamp());
    }

    // Generate etag
    $etag = sprintf(
        '%s-%s-%s-%d-%d',
        $image->md5,
        $textTop,
        $textBottom,
        (int) $width,
        (int) $height
    );
    $app->etag(md5($etag));

    // Resize the result image
    if ($width || $height) {
        ImageTools::resize($bytes, $width, $height, $adj);
    } else {
        echo $bytes;
    }
});

//********************************************* ADMINS *********************************************//

if (! $app->secured) {
    return;
}

/**
 * Update meme.
 */
$app->post('/memes/:id', function($id) use ($app) {
    $meme = $app
        ->dm
        ->getRepository('Meme')
        ->find($id);

    if (! $meme) {
        $app->pass();
    }

    $request = $app->request;

    if ($status = $request->put('status')) {
        if (! in_array($status, Meme::getStatuses())) {
            return render_json([
                'status'  => 'error',
                'message' => sprintf('Invalid status %s', $status)
            ]);
        }

        $meme->status = $status;
    }

    // Persist
    $app->dm->persist($meme);
    $app->dm->flush();

    // Yay!
    render_json([
        'status' => 'success',
        'data'   => $meme
    ]);
});

/**
 * Delete meme.
 */
$app->delete('/memes/:id', function($id) use ($app) {
    $meme = $app
        ->dm
        ->getRepository('Meme')
        ->find($id);

    if (! $meme) {
        return $app->notFound();
    }

    $app->dm->remove($meme);
    $app->dm->flush();

    $app->redirect('/');
});