<?php

namespace mg;

class ImageTools
{
    const FONT_PADDING  = 10;
    const FONT_SIZE_MAX = 60;
    const FONT_SIZE_MIN = 40;

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

    /**
     * Create meme image.
     *
     * @param string $bytes
     * @param string $top
     * @param string $bottom
     * @param int    $quality
     *
     * @return
     */
    public static function createMeme($bytes, $topText, $bottomText, $quality = 85)
    {
        $fontFile = self::getFontFile();

        // All upper case!
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
            self::fitText($imageWidth, $topText, $textLines, $fontSize);

            // Add lines
            $offset = self::FONT_PADDING;

            foreach ($textLines as $line) {
                $textWidth  = self::getTextWidth($line, $fontSize);
                $textHeight = self::getTextHeight($line, $fontSize);

                // Don't use more than half the vertical space
                if ($offset + $textHeight > $imageHeight / 2) {
                    break;
                }

                $textX = ($imageWidth - $textWidth - self::FONT_PADDING) / 2;
                $textY = $textHeight + $offset;

                self::addStrokedText($image, $fontSize, $textX, $textY, $line);

                $offset += $textHeight + self::FONT_PADDING;
            }
        }

        // Bottom text
        if ($bottomText) {
            self::fitText($imageWidth, $bottomText, $textLines, $fontSize);

            // Calculate the text height to calculate the offset
            $textHeight = self::getTextHeight($textLines[0], $fontSize);
            $offset = $imageHeight - (self::FONT_PADDING + $textHeight) * count($textLines);

            // Add lines
            foreach ($textLines as $line) {
                $textWidth  = self::getTextWidth($line, $fontSize);

                $textX = ($imageWidth - $textWidth - self::FONT_PADDING) / 2;
                $textY = $textHeight + $offset;

                self::addStrokedText($image, $fontSize, $textX, $textY, $line);

                $offset += $textHeight + self::FONT_PADDING;
            }
        }

        return imagejpeg($image, null, $quality);
    }

    /**
     * Breaks text into multiple lines
     *
     * @param int    $imageWidth
     * @param string $text
     * @param array  $textLines
     * @param int    $fontSize
     */
    private static function fitText($imageWidth, $text, &$textLines, &$fontSize)
    {
        $textLines = [];
        $fontSize = self::FONT_SIZE_MIN;

        // Strategy 1. Reasonable user.
        if (! self::textTooLong($text, $fontSize, $imageWidth)) {
            $textLines = [ $text ];
            $fontSize = self::FONT_SIZE_MIN;

            while (
                ! self::textTooLong($text, $fontSize + 1, $imageWidth) &&
                $fontSize + 1 <= self::FONT_SIZE_MAX
            ) {
                $fontSize++;
            }
        }
        // Strategy 2. Chatter.
        else {
            $words = str_word_count($text, 1, '\'"-.,;:!?@#$%^&*()_=');
            $buffer = [];

            // Lets add the word and see if line's not too long
            foreach ($words as $word) {
                $buffer[] = $word;

                // Measure the line
                $lineStr = implode(' ', $buffer);

                // Adding that word made the line too long?
                if (self::textTooLong($lineStr, $fontSize, $imageWidth)) {
                    array_pop($buffer);
                    $textLines[] = implode(' ', $buffer);
                    $buffer = [ $word ];
                }
            }

            // Anything in the buffer?
            if ($buffer) {
                $textLines[] = implode(' ', $buffer);
            }
        }
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
        return APP_ROOT . '/app/fonts/Franchise-Bold-hinted.ttf';
    }

    /**
     * Returns text width.
     *
     * @param string $text
     * @param string $fontSize
     *
     * @return int
     */
    private static function getTextWidth($text, $fontSize)
    {
        list(
            $llx, $lly,
            $lrx, $lry,
            $urx, $ury,
            $ulx, $uly
        ) = imagettfbbox($fontSize, 0, self::getFontFile(), $text);

        return abs($lrx - $llx);
    }

    /**
     * Returns text height.
     *
     * @param string $text
     * @param string $fontSize
     *
     * @return int
     */
    private static function getTextHeight($text, $fontSize)
    {
        list(
            $llx, $lly,
            $lrx, $lry,
            $urx, $ury,
            $ulx, $uly
        ) = imagettfbbox($fontSize, 0, self::getFontFile(), $text);

        return abs($ury - $lry);
    }

    /**
     * Returns true if text is too long for the given the font size and image width.
     *
     * @param string $text
     * @param int    $fontSize
     * @param int    $imageWidth
     *
     * @return bool
     */
    private static function textTooLong($text, $fontSize, $imageWidth)
    {
        return self::getTextWidth($text, $fontSize) > $imageWidth - 2 * self::FONT_PADDING;
    }
}