<?php

namespace EloquentVersioned\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AppendedVersioning
 *
 * @package EloquentVersioned\Traits
 */
trait AppendedVersioning
{
    use Versioned;

    /*
     * ACCESSORS + MUTATORS
     */

    /**
     * @return mixed
     */
    public function getIdAttribute()
    {
        return array_key_exists('id', $this->attributes) ? $this->attributes[$this->primaryKey] : null;
    }

    /*
     * ELOQUENT OVERRIDES
     */

    /**
     * @return string
     */
    public function getKeyName()
    {
        return parent::getKeyName();
    }

    /**
     * Save a new version of the model
     *
     * @param array $options
     *
     * @return bool
     */
    public function save(array $options = [])
    {
        $query = $this->newQueryWithoutScopes();

        $db = $this->getConnection();

        // If the "saving" event returns false we'll bail out of the save and
        // return false, indicating that the save failed. This provides a chance
        // for any listeners to cancel save operations if validations fail or
        // whatever.
        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        // If the model already exists in the database we can just update our
        // record that is already in this database using the current IDs in this
        // "where" clause to only update this model. Otherwise, we'll just
        // insert them.
        if ($this->exists) {
            if ($this->isDirty()) {
                $saved = $db->transaction(function () use ($query, $db, $options) {
                    $oldVersion = $this->replicate();
                    $oldVersion->forceFill($this->original);
                    $oldVersion->{$this->primaryKey} = null;
                    $oldVersion->{static::getIsCurrentVersionColumn()} = false;

                    $this->performVersionedInsert($query, $oldVersion);

                    // trigger the update event
                    if ($this->fireModelEvent('updating') === false) {
                        return false;
                    }

                    $this->{static::getVersionColumn()} = static::getNextVersion($this->{static::getModelIdColumn()});
                    $saved = $this->performUpdate($query, $options);

                    if ($saved) {
                        $this->fireModelEvent('updated', false);
                    }

                    return $saved;
                });
            }
        }

        // If the model is brand new, we'll insert it into our database and set the
        // ID attribute on the model to the value of the newly inserted row's ID
        // which is typically an auto-increment value managed by the database.
        else {
            $this->{static::getModelIdColumn()} = static::getNextModelId();
            $saved = $this->performInsert($query, $options);
        }

        if ($saved) {
            $this->finishSave($options);
        }

        return $saved;
    }

    /**
     * @param Builder $query
     * @param Model   $model
     */
    public function performVersionedInsert(Builder $query, Model $model)
    {
        return $query->insert($model->getAttributes());
    }
}
