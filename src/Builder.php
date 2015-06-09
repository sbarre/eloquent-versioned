<?php namespace EloquentVersioned;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class Builder extends EloquentBuilder
{

    const COLUMN_MODEL_ID = 'model_id';
    const COLUMN_VERSION = 'version';
    const COLUMN_IS_CURRENT_VERSION = 'is_current_version';

    /**
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    public function first($columns = array('*'))
    {
        if (count($this->query->orders) == 0) {
            $this->orderBy(static::COLUMN_MODEL_ID);
        }

        return parent::first($columns);
    }

    /**
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Model[]
     */
    public function getModels($columns = array('*'))
    {
        if (count($this->query->orders) == 0) {
            $this->orderBy(static::COLUMN_MODEL_ID);
        }

        return parent::getModels($columns);
    }

    /**
     * A method to use with versioned scopes to retrieve a collection of
     * non-current-version models (either with or without the current version)
     *
     * @param       $id
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findAll($id, $columns = array('*'))
    {
        return $this->findMany([$id], $columns);
    }
}
