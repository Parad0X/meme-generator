<?php

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="memes")
 */
class Meme implements JsonSerializable
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String(name="text_top") */
    public $textTop;

    /** @ODM\String(name="text_bottom") */
    public $textBottom;

    /** @ODM\ReferenceOne(targetDocument="Image",simple=true,cascade="all") */
    public $image;

    /** @ODM\Date(name="created_at") */
    public $createdAt;

    /**
     * Constructor.
     *
     * @param Image  $image
     * @param string $textTop
     * @param string $textBottom
     */
    public function __construct(Image $image, $textTop, $textBottom)
    {
        $this->image      = $image;
        $this->textTop    = $textTop;
        $this->textBottom = $textBottom;
        $this->createdAt  = new DateTime();
    }

    /**
     * JsonSerializable::jsonSerialize
     */
    public function jsonSerialize()
    {
        return [
            'id'          => (string) $this->id,
            'image'       => $this->image,
            'text_top'    => $this->textTop,
            'text_bottom' => $this->textBottom
        ];
    }
}