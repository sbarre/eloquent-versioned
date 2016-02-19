<?php

namespace EloquentVersioned\Traits;

use EloquentVersioned\Builder as VersionedBuilder;
use EloquentVersioned\Scopes\VersioningScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait Versioned
{

    protected $isVersioned = true;

    protected $hideVersioned = [
        VersionedBuilder::COLUMN_IS_CURRENT_VERSION,
        VersionedBuilder::COLUMN_MODEL_ID
    ];

    public static function bootVersioned()
    {
        static::addGlobalScope(new VersioningScope());
    }

    protected function getHideVersioned($hide = array())
    {
        return array_merge($hide, $this->hideVersioned);
    }

    /*
     * ACCESSORS + MUTATORS
     */

    /**
     * @return mixed
     */
    public function getIdAttribute()
    {
        return ($this->{static::getIsCurrentVersionColumn()} == 0) ?
            $this->attributes[$this->primaryKey] :
            $this->attributes[static::getModelIdColumn()];
    }

    /*
     * ELOQUENT OVERRIDES
     */

    /**
     * @return string
     */
    public function getKeyName()
    {
        return $this->isVersioned ? $this->getModelIdColumn() : $this->primaryKey;
    }

    /**
     * @param $query
     *
     * @return VersionedBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new VersionedBuilder($query);
    }

    /**
     * @return array
     */
    public function attributesToArray($hide = array())
    {
        $parentAttributes = parent::attributesToArray();

        if ((!$this->isVersioned) || ($this->{static::getIsCurrentVersionColumn()} == 0)) {
            return $parentAttributes;
        }

        $attributes = [];
        foreach ($parentAttributes as $key => $value) {
            if (!in_array($key, $this->getHideVersioned($hide))) {
                $attributes[$key] = $value;
            }
        }

        return $attributes;
    }

    /**
     * @param Builder $query
     *
     * @return Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        $query->where($this->getKeyName(), '=', $this->getKeyForSaveQuery())
            ->where($this->getQualifiedIsCurrentVersionColumn(), 1);

        return $query;
    }

    /**
     * @param array $options
     *
     * @return mixed
     */
    public function saveMinor(array $options = [])
    {
        return parent::save($options);
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

            $dirty = $this->isDirty();

            if (count($dirty)>0) {

                $saved = $db->transaction(function () use ($query, $db, $options) {

                    // create old version, save new one in place
                    $oldVersion = $this->replicate([$this->primaryKey]);
                    $oldVersion->forceFill($this->original);
                    unset($oldVersion->attributes[$this->primaryKey]);
                    $oldVersion->{static::getIsCurrentVersionColumn()} = 0;

                    $oldVersion->performInsert($query, ['timestamps' => false]);

                    // trigger the update event
                    if ($this->fireModelEvent('updating') === false) {
                        return false;
                    }

                    $this->updated_at = $this->freshTimestamp();
                    $this->{static::getVersionColumn()} = static::getNextVersion($this->{static::getModelIdColumn()});
	                $this->{static::getIsCurrentVersionColumn()} = 1;
	                $this->updated_at = $this->freshTimestamp();

                    $saved = $this->performUpdate($query, $options);

	                // clear any other current version columns - we have to do this
	                // because we might be reverting from a previous non-current model,
	                // and not just saving the currently-current(?) model.
	                if ($saved) {

		                $db->table( (new static)->getTable() )
			                ->where( static::getModelIdColumn(), $this->{static::getModelIdColumn()} )
			                ->where( static::getIsCurrentVersionColumn(), 1 )
	                        ->where( $this->primaryKey, '<>', $this->attributes[ $this->primaryKey ] )
			                ->update( [ static::getIsCurrentVersionColumn() => 0] );
	                }

                    // this returns from the closure, not the function!
                    return $saved;

                });
            } else {
                $saved = true;
            }
        }

        // If the model is brand new, we'll insert it into our database,
        // then set the model_id to the id of the newly created record.
        else {
            $this->{static::getIsCurrentVersionColumn()} = 1;
            $saved = $this->performInsert($query, $options);
            $this->{static::getModelIdColumn()} = $this->attributes[$this->primaryKey];
            $saved = $saved && $this->performUpdate($query, $options);
        }

        if ($saved) {
            $this->finishSave($options);
        }

        return $saved;
    }

    protected function insertAndSetId(Builder $query, $attributes)
    {
        $id = $query->insertGetId($attributes, $keyName = $this->primaryKey);

        $this->setAttribute($keyName, $id);
    }

    /*
     * EXTENSIONS
     */

    /**
     * @param Builder $query
     * @param Model   $model
     */
    protected function performVersionedInsert(Builder $query, array $options = array())
    {
        // First we'll need to create a fresh query instance and touch the creation and
        // update timestamps on this model, which are maintained by us for developer
        // convenience. After, we will just continue saving these model instances.
        if ($this->timestamps && array_get($options, 'timestamps', true)) {
            $this->updateTimestamps();
        }

        // If the model has an incrementing key, we can use the "insertGetId" method on
        // the query builder, which will give us back the final inserted ID for this
        // table from the database. Not all tables have to be incrementing though.
        $attributes = $this->attributes;

        if ($this->incrementing) {
            $this->insertAndSetId($query, $attributes);
        }

        // If the table is not incrementing we'll simply insert this attributes as they
        // are, as this attributes arrays must contain an "id" column already placed
        // there by the developer as the manually determined key for these models.
        else {
            $query->insert($attributes);
        }

        // We will go ahead and set the exists property to true, so that it is set when
        // the created event is fired, just in case the developer tries to update it
        // during the event. This will allow them to do so and run an update here.
        $this->exists = true;

        return true;

    }

    /**
     * @param bool $isVersioned
     *
     * @return $this
     */
    public function setIsVersioned($isVersioned = true)
    {
        $this->isVersioned = $isVersioned;

        return $this;
    }

    /**
     * @param int $modelId
     *
     * @return int
     */
    public static function getNextVersion($modelId)
    {
        return (new static)->getConnection()->table((new static)->getTable())
            ->where(static::getModelIdColumn(), $modelId)
            ->max(static::getVersionColumn()) + 1;
    }

    /**
     * @return string
     */
    public static function getModelIdColumn()
    {
        return VersionedBuilder::COLUMN_MODEL_ID;
    }

    /**
     * @return string
     */
    public static function getQualifiedModelIdColumn()
    {
        return (new static)->getTable() . '.' . static::getModelIdColumn();
    }

    /**
     * @return string
     */
    public static function getVersionColumn()
    {
        return VersionedBuilder::COLUMN_VERSION;
    }

    /**
     * @return string
     */
    public static function getQualifiedVersionColumn()
    {
        return (new static)->getTable() . '.' . static::getVersionColumn();
    }

    /**
     * @return string
     */
    public static function getIsCurrentVersionColumn()
    {
        return VersionedBuilder::COLUMN_IS_CURRENT_VERSION;
    }

    /**
     * @return string
     */
    public static function getQualifiedIsCurrentVersionColumn()
    {
        return (new static)->getTable() . '.' . static::getIsCurrentVersionColumn();
    }

    /**
     * @return mixed
     */
    public static function withOldVersions()
    {
        return (new static)->newQueryWithoutScope(new VersioningScope);
    }

    /**
     * @return mixed
     */
    public static function onlyOldVersions()
    {
        return (new static)->newQueryWithoutScope(new VersioningScope)
            ->where(static::getQualifiedIsCurrentVersionColumn(), 0);
    }

    /**
     * @return mixed
     */
    public function getPreviousModel()
    {
        if ($this->version === 1) {
            return null;
        }

        return $this->withOldVersions()
            ->where('model_id', $this->model_id)
            ->where('version', ($this->version - 1))
            ->first();
    }

    /**
     * @return mixed
     */
    public function getNextModel()
    {
        if ($this->is_current_version === true) {
            return null;
        }

        return $this->withOldVersions()
            ->where('model_id', $this->model_id)
            ->where('version', ($this->version + 1))
            ->first();
    }

    /**
     * Switch the model to a different version
     *
     * @param int $version
     */
    public function revertTo($version)
    {
        $model = $this->onlyOldVersions()
	        ->where('version',intval($version))
	        ->where('model_id', $this->model_id)
	        ->first();
        if ($model) {
	        $revertedAttributes = array_except($model->attributes,[$this->primaryKey]);
	        $this->forceFill($revertedAttributes);
            $this->save();
        }
    }

}
