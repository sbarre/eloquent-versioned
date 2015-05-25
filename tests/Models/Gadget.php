<?php namespace EloquentVersioned\Tests\Models;

class Gadget extends BaseVersionedModel
{
    protected $table = 'gadgets';

    protected $fillable = array('*');

    public function widget()
    {
        return $this->belongsTo('EloquentVersioned\Tests\Models\Widget', 'widget_id');
    }

    public function doodad()
    {
        return $this->belongsTo('EloquentVersioned\Tests\Models\Doodad', 'doodad_id');
    }

}