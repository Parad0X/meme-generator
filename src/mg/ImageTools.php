<?php

namespace mg;

class ImageTools
{
    const TEXT_TOP    = 'top';
    const TEXT_BOTTOM = 'bottom';

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
    public static function resize(Image $image, $width = null, $height = null, $adjust = false, $quality = 90)
    {
        $bytes = $image
            ->getFile()
            ->getBytes();

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

    /**
     *
     *
     * @param string $bytes
     * @param string $top
     * @param string $bottom
     *
     * @return
     */
    public static function createMeme($bytes, $topText, $bottomText)
    {
        $fontFile    = self::getFontFile();
        $fontSize    = 51;
        $fontPadding = 10;

        // All uppser case!
        $topText    = strtoupper($topText);
        $bottomText = strtoupper($bottomText);

        if (! $image = imagecreatefromstring($bytes)) {
            throw new RuntimeException('imagecreatefromstring failed');
        }

        list(
            $imageWidth,
            $imageHeight,
            $mimeType
        ) = getimagesizefromstring($bytes);

        // Top text
        if ($topText) {
            $text = $topText;

            // Adjust font size to fint the width
            while (--$fontSize > 35) {
                list(
                    $llx, $lly,
                    $lrx, $lry,
                    $urx, $ury,
                    $ulx, $uly
                ) = imagettfbbox($fontSize, 0, $fontFile, $text);

                $textWidth  = abs($lrx - $llx);
                $textHeight = abs($ury - $lry);

                if ($textWidth + $fontPadding * 2 < $imageWidth) {
                    break;
                }
            }

            // Calculate text offsets
            $textX = ($imageWidth - $textWidth) / 2;
            $textY = $textHeight + $fontPadding;

            self::addStrokedText($image, $fontSize, $textX, $textY, $text);
        }

        // Bottom text
        if ($bottomText) {
            $text = $bottomText;

            // Adjust font size to fint the width
            while (--$fontSize > 35) {
                list(
                    $llx, $lly,
                    $lrx, $lry,
                    $urx, $ury,
                    $ulx, $uly
                ) = imagettfbbox($fontSize, 0, $fontFile, $text);

                $textWidth  = abs($lrx - $llx);
                $textHeight = abs($ury - $lry);

                if ($textWidth + $fontPadding * 2 < $imageWidth) {
                    break;
                }
            }

            // Calculate text offsets
            $textX = ($imageWidth - $textWidth) / 2;
            $textY = $imageHeight - $fontPadding;

            self::addStrokedText($image, $fontSize, $textX, $textY, $text);
        }

        return imagejpeg($image, null, 85);
    }

    /**
     * Adds stroked text to an image.
     *
     * @param resource $image
     * @param int      $fontSize
     * @param int      $textX
     * @param int      $textY
     * @param string   $text
     */
    private static function addStrokedText($image, $fontSize, $textX, $textY, $text)
    {
        $fontFile   = self::getFontFile();
        $strokeSize = 2;

        // Stroke
        $strokeColor = imagecolorallocate($image, 0, 0, 0);
        for ($x = $textX - $strokeSize; $x <= $textX + $strokeSize; $x++) {
            for ($y = $textY - $strokeSize; $y <= $textY + $strokeSize; $y++) {
                imagettftext($image, $fontSize, 0, $x, $y, $strokeColor, $fontFile, $text);
            }
        }

        // Text
        $textColor = imagecolorallocate($image, 255, 255, 255);
        imagettftext($image, $fontSize, 0, $textX, $textY, $textColor, $fontFile, $text);
    }

    /**
     * Returns font file path.
     *
     * @return string
     */
    private static function getFontFile()
    {
        return APP_ROOT . '/app/fonts/Dosis-SemiBold.ttf';
    }
}