<?php

namespace mg;

class ImageTools
{
    /**
     * Returns image width, height and type.
     *
     * @param string $filename File path
     *
     * @return array
     * @throws RuntimeException If given file is not a valid image
     */
    public static function getImageDetails($filename)
    {
        if (! $details = getimagesize($filename)) {
            throw new RuntimeException(sprintf('%s is not an image', $filename));
        }

        list($width, $height, $type) = $details;

        return [$width, $height, $type];
    }

    /**
     * {@inheritdoc}
     */
    public static function resize($bytes, $width = null, $height = null, $adjust = false, $quality = 90)
    {
        list(
            $originalWidth,
            $originalHeight,
            $mimeType
        ) = getimagesizefromstring($bytes);

        $sx = $sy = 0;
        $dx = $dy = 0;

        $sourceWidth  = $originalWidth;
        $sourceHeight = $originalHeight;

        switch (true) {
            case !is_null($width) && !is_null($height):
                switch ($adjust) {
                    case 'crop':
                        $dstWidth  = $width;
                        $dstHeight = $height;

                        // both landscape
                        $expectedHeight = ($originalWidth / $dstWidth) * $dstHeight;
                        if ($expectedHeight <= $originalHeight) {
                            $sourceHeight = $expectedHeight;
                            $sy = (int) ($originalHeight - $expectedHeight) / 2;
                        } else {
                            $expectedWidth = ($originalHeight / $dstHeight) * $dstWidth;
                            $sourceWidth = $expectedWidth;
                            $sx = (int) ($originalWidth - $expectedWidth) / 2;
                        }
                        break;
                    case 'fill':
                        $newWidth  = $width;
                        $newHeight = $height;

                        $expectedHeight = $originalHeight / ($originalWidth / $newWidth);
                        if ($expectedHeight < $newHeight) {
                            $dstHeight = $expectedHeight;
                            $dstWidth = $newWidth;
                            $dy = ($newHeight - $dstHeight) / 2;
                        } else {
                            $dstHeight = $newHeight;
                            $dstWidth = $originalWidth / ($originalHeight / $newHeight);
                            $dx = ($newWidth - $dstWidth) / 2;
                        }
                        break;
                    default:
                        // try resizing width and see if it fits
                        $dstWidth = $width;
                        $dstHeight = $originalHeight / ($originalWidth / $width);

                        if ($dstHeight > $height) {
                            $dstHeight = $height;
                            $dstWidth = $originalWidth / ($originalHeight / $height);
                        }
                }
                break;
            case !is_null($width):
                $dstWidth = $width;
                if ($dstWidth > $originalWidth) {
                    $dstWidth = $originalWidth;
                }
                $dstHeight = (int) ($dstWidth * $originalHeight) / $originalWidth;
                break;
            case !is_null($height):
                $dstHeight = $height;
                if ($dstHeight > $originalHeight) {
                    $dstHeight = $originalHeight;
                }
                $dstWidth = (int) ($dstHeight * $originalWidth) / $originalHeight;
                break;
            default:
                throw new InvalidArgumentException('width and height cannot be both null');
        }

        // resize image
        $originalImage = imagecreatefromstring($bytes);

        if (!isset($newWidth)) {
            $newWidth  = $dstWidth;
            $newHeight = $dstHeight;
        }

        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        // Fill image with white color
        $white = imagecolorallocate($resizedImage, 255, 255, 255);
        imagefill($resizedImage, 0, 0, $white);

        // Copy resampled image
        imagecopyresampled(
            $resizedImage,
            $originalImage,
            $dx,
            $dy,
            $sx,
            $sy,
            $dstWidth,
            $dstHeight,
            $sourceWidth,
            $sourceHeight
        );

        // Return resized image data
        return imagejpeg($resizedImage, null, $quality);
    }
}