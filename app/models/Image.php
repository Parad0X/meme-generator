<?php

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="fs")
 */
class Image implements JsonSerializable
{
    /** @ODM\Id */
    public $id;

    /** @ODM\File */
    public $file;

    /** @ODM\Field */
    public $length;

    /** @ODM\Field */
    public $chunkSize;

    /** @ODM\Field */
    public $md5;

    /** @ODM\Int */
    public $width;

    /** @ODM\Int */
    public $height;

    /** @ODM\String @ODM\Index */
    public $type;

    /** @ODM\Date */
    public $uploadDate;

    /**
     * Constructor.
     *
     * @param string $file
     * @param int    $width
     * @param int    $height
     * @param string $type
     */
    public function __construct($file, $width, $height, $type = null)
    {
        $this->file   = $file;
        $this->width  = $width;
        $this->height = $height;
        $this->type   = $type;
    }

    /**
     * Return's image url.
     *
     * @param string
     *
     */
    public function getUrl($width = null, $height = null, $adj = null)
    {
        $url = '/images/' . $this->id;

        if ($width) {
            $url .= '-' . (int) $width;

            if ($height) {
                $url .= '-' . (int) $height;
            }

            if ($adj) {
                $url .= '-' . $adj;
            }
        }

        return $url;
    }

    /**
     * Returns file.
     *
     * @return Doctrine\MongoDB\GridFSFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * JsonSerializable::jsonSerialize
     */
    public function jsonSerialize()
    {
        return [
            'id'     => (string) $this->id,
            'width'  => $this->width,
            'height' => $this->height,
            'type'   => $this->type
        ];
    }
}