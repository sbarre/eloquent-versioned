<?php

namespace EloquentVersioned;

use EloquentVersioned\Exceptions\IncompatibleModelMismatchException;
use Illuminate\Database\Eloquent\Model;

class VersionDiffer
{

    private $ignoredFields = [
        'is_current_version',
        'version',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function diff(Model $left, Model $right)
    {
        if (($left instanceof $right) === false) {
            throw new IncompatibleModelMismatchException;
        }

        $changes = [];
        $leftAttributes = array_except($left->getAttributes(), $this->ignoredFields);
        $rightAttributes = array_except($right->getAttributes(), $this->ignoredFields);

        $differences = array_diff($leftAttributes, $rightAttributes);

        foreach ($differences as $key => $value) {
            $changes[$key][0] = $leftAttributes[$key];
            $changes[$key][1] = $rightAttributes[$key];
            $changes[$key]['left'] = $leftAttributes[$key];
            $changes[$key]['right'] = $rightAttributes[$key];
        }

        return $changes;
    }
}
