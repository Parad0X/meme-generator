<?php

namespace mg\Pagination\Adapter;

use Doctrine\MongoDB\Cursor;
use Alex\Pagination\Adapter\AdapterInterface;

/**
 * Adapter class for a Mongo Cursor object.
 *
 * @author Andrei Shevtsov <andrei@parad0x.me>
 */
class MongoCursorAdapter implements AdapterInterface
{
    /**
     * @var Cursor
     */
    protected $cursor;

    /**
     * Constructor.
     *
     * @param Cursor $cursor
     */
    public function __construct(Cursor $cursor)
    {
        $this->cursor = $cursor;
    }

    /**
     * {@inheritdoc}
     */
    public function get($offset, $limit)
    {
        $this->cursor->reset();

        return $this
            ->cursor
            ->skip($offset)
            ->limit($limit)
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->cursor);
    }
}
