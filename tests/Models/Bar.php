<?php
namespace EloquentVersioned\Tests\Models;

class Bar extends BaseVersionedModel
{
    protected $minorAttributes = [
        'name',
    ];

    protected $table = 'bars';

    protected $fillable = ['*'];
}
