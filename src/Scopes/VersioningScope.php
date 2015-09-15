<?php namespace EloquentVersioned\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ScopeInterface;

class VersioningScope implements ScopeInterface
{

    protected $extensions = ['WithOldVersions', 'OnlyOldVersions'];

    public function apply(Builder $builder, Model $model)
    {
        $builder->where($model->getQualifiedIsCurrentVersionColumn(), 1);

        $this->extend($builder);
    }

    public function remove(Builder $builder, Model $model)
    {
        $column = $model->getQualifiedIsCurrentVersionColumn();

        $query = $builder->getQuery();
        $bindings = $query->getBindings();

        $bindKey = 0;

        foreach ((array)$query->wheres as $key => $value) {
            if (strtolower($value['type']) == 'basic') {
                $bindKey++;
            }
            if ($value['column'] == $column) {
                if ($bindings[$bindKey - 1] == 1) {
                    unset($bindings[$bindKey - 1]);
                }
                unset($query->wheres[$key]);
            }
        }

        $query->wheres = array_values($query->wheres);

        $builder->setBindings(array_values($bindings));
    }

    /**
     * @param Builder $builder
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }
    }

    /**
     * @param Builder $builder
     */
    protected function addWithOldVersions(Builder $builder)
    {
        $builder->macro('withOldVersions', function (Builder $builder) {
            $this->remove($builder, $builder->getModel());

            return $builder;
        });
    }

    /**
     * @param Builder $builder
     */
    protected function addOnlyOldVersions(Builder $builder)
    {
        $builder->macro('onlyOldVersions', function (Builder $builder) {
            $model = $builder->getModel();
            $this->remove($builder, $model);

            $builder->getQuery()->where($model->getQualifiedIsCurrentVersionColumn(),
                0);

            return $builder;
        });
    }
}
