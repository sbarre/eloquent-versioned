<?php

namespace EloquentVersioned;

use EloquentVersioned\Exceptions\IncompatibleModelMismatchException;
use Illuminate\Database\Eloquent\Model;

class VersionDiffer
{

	/**
	 * @var array
	 */
    private $ignoredFields = [
        'is_current_version',
        'version',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @param Model $left
     * @param Model $right
     * @param array $ignoredFields additional fields to ignore in the diff
     *
     * @return array
     * @throws IncompatibleModelMismatchException
     */
    public function diff(Model $left, Model $right, $ignoredFields = [])
    {
        if (($left instanceof $right) === false) {
            throw new IncompatibleModelMismatchException;
        }

	    $ignoredFields = array_merge($this->ignoredFields, $ignoredFields);

        $changes = [];
        $leftAttributes = array_except($left->getAttributes(), $ignoredFields);
        $rightAttributes = array_except($right->getAttributes(), $ignoredFields);

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
