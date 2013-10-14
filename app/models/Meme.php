<?php

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="memes")
 * @ODM\Index(keys={"status":1,"created_at":-1})
 */
class Meme implements JsonSerializable
{
    const STATUS_NEW       = 'new';
    const STATUS_PUBLISHED = 'published';
    const STATUS_REJECTED  = 'rejected';

    /** @ODM\Id */
    public $id;

    /** @ODM\String(name="text_top") */
    public $textTop;

    /** @ODM\String(name="text_bottom") */
    public $textBottom;

    /** @ODM\ReferenceOne(targetDocument="Image",simple=true,cascade="all") */
    public $image;

    /** @ODM\String */
    public $status = self::STATUS_NEW;

    /** @ODM\Date(name="created_at") */
    public $createdAt;

    /**
     * Returns a list of valid statuses.
     *
     * @return array
     */
    public static function getStatuses()
    {
        return [ self::STATUS_NEW, self::STATUS_PUBLISHED, self::STATUS_REJECTED ];
    }

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

    public function getImage()
    {
        $this->image->__load();
        return $this->image;
    }

    /**
     * Returns meme's share url.
     *
     * @return string
     */
    public function getShareUrl()
    {
        return APP_URL . '/memes/' . $this->id;
    }

    /**
     * Returns text to share.
     *
     * @return string
     */
    public function getShareText()
    {
        return 'Get Your Meme On With Season Four Of The Walking Dead';
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
            'text_bottom' => $this->textBottom,
            'status'      => $this->status
        ];
    }
}