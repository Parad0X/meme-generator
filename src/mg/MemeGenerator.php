<?php

namespace mg;

class MemeGenerator
{
    const FONT_FILE     = 'Franchise-Bold-hinted.ttf';
    const FONT_PADDING  = 10;
    const FONT_SIZE_MAX = 60;
    const FONT_SIZE_MIN = 40;

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
        return APP_ROOT . '/app/fonts/' . self::FONT_FILE;
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