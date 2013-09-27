<?php

/**
 * ImagesController.
 */

use mg\ImageTools;

/**
 * Upload image action.
 */
$app->post('/api/images', function() use ($app) {
    if (!isset($_FILES['image'])) {
        return $app->pass();
    }

    $file = $_FILES['image'];

    // Was file uploaded ok?
    if ($file['error']) {
        return render_json([
            'status' => 'error',
            'data'   => 'Image upload error.'
        ]);
    }

    // Valid image?
    try {
        list($width, $height, $type) = ImageTools::getImageDetails($file['tmp_name']);
    } catch (Exception $e) {
        return render_json([
            'status' => 'error',
            'data'   => 'Uploaded file is not a valid image: ' . $e->getMessage()
        ]);
    }

    // Create new image model
    $image = new Image($file['tmp_name'], $width, $height);

    // Save it
    $app->dm->persist($image);
    $app->dm->flush();

    // Response
    render_json([
        'status' => 'success',
        'data'   => $image
    ]);
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

    // Response
    $app
        ->response
        ->headers
        ->set('Content-Type', $mimeType);

    if ($width || $height) {
        ImageTools::resize($bytes, $width, $height, $adj);
    } else {
        echo $bytes;
    }
});