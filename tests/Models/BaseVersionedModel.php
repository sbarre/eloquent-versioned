<?php namespace EloquentVersioned\Tests\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use EloquentVersioned\Traits\Versioned;

class BaseVersionedModel extends Eloquent
{
    use Versioned;

}