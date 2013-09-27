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
            'id'          => (string) $this->id,
            'width'       => $this->width,
            'height'      => $this->height,
            'type'        => $this->type
        ];
    }
}