<?php namespace EloquentVersioned\Tests\Models;

class Doodad extends BaseVersionedModel {

    protected $table = 'doodads';

    protected $fillable = array('*');

    public function gadget()
    {
        return $this->belongsTo('EloquentVersioned\Tests\Models\Gadget', 'gadget_id');
    }

    public function widget()
    {
        return $this->belongsTo('EloquentVersioned\Tests\Models\Widget', 'widget_id');
    }

}