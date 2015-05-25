<?php namespace EloquentVersioned\Tests\Models;

class Widget extends BaseVersionedModel {

    protected $table = 'widgets';

    protected $fillable = array('*');

    public function gadget()
    {
        return $this->belongsTo('EloquentVersioned\Tests\Models\Gadget', 'gadget_id');
    }

    public function doodad()
    {
        return $this->belongsTo('EloquentVersioned\Tests\Models\Doodad', 'doodad_id');
    }

}