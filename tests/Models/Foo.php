<?php
namespace EloquentVersioned\Tests\Models;

use Rhumsaa\Uuid\Uuid;

class Foo extends BaseVersionedModel
{
    protected $table = 'foos';

    protected $fillable = ['*'];

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!array_key_exists($model->primaryKey, $model->attributes)) {
                $model->attributes[$model->primaryKey] = $model->generateNewId();
            }
        });
    }

    public function generateNewId()
    {
        return Uuid::uuid4();
    }

    public static function getNextModelId() {
        return Uuid::uuid4();
    }
}
