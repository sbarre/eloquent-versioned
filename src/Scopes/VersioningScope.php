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
            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * @param Builder $builder
     */
    protected function addOnlyOldVersions(Builder $builder)
    {
        $builder->macro('onlyOldVersions', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)->where($model->getQualifiedIsCurrentVersionColumn(),0);

            return $builder;
        });
    }
}
