<?php

/**
 * ImagesController.
 */

use mg\ImageTools;

/**
 * Show image.
 *
 * @param string $id Image id
 */
$app->get('/images/:id', function($id) use ($app) {
    $adj    = $app->request->get('adj');
    $width  = $app->request->get('width');
    $height = $app->request->get('height');

    $image = $app
        ->dm
        ->getRepository('Image')
        ->find($id);

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

    // Caching
    $app->etag($image->md5);
    $app->lastModified($image->uploadDate->getTimestamp());

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
 * Upload image.
 */
$app->post('/api/images', function() use ($app) {
    if (!isset($_FILES['image'])) {
        return $app->pass();
    }

    $file = $_FILES['image'];

    // Was file uploaded ok?
    if ($file['error']) {
        return $app->redirect('/admin?error=1');
    }

    // Valid image?
    try {
        list($width, $height, $type) = ImageTools::getImageDetails($file['tmp_name']);
    } catch (Exception $e) {
        return $app->redirect('/admin?error=2');
    }

    // Create new image model
    $image = new Image($file['tmp_name'], $width, $height);

    // Save it
    $app->dm->persist($image);
    $app->dm->flush();

    // Response
    return $app->redirect('/admin');
});

/**
 * Delete image.
 *
 * @param string $id Image id
 */
$app->delete('/api/images/:id', function($id) use ($app) {
    $image = $app
        ->dm
        ->getRepository('Image')
        ->find($id);

    if (! $image) {
        return $app->pass();
    }

    $app->dm->remove($image);
    $app->dm->flush();

    // Gone
    render_json([
        'status' => 'sucess'
    ]);
});