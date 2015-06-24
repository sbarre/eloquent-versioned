<?php

namespace EloquentVersioned\Tests\Models;

use EloquentVersioned\Traits\AppendedVersioning;
use Illuminate\Database\Eloquent\Model;

class Thingy extends Model
{
    use AppendedVersioning;

    protected $table = 'thingies';

    protected $fillable = ['*'];
}
