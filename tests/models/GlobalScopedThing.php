<?php

namespace Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GlobalScopedThing extends Model
{
    use SoftDeletes;

    protected $fillable = ['name'];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('name', function (Builder $builder) {
            $builder->where('name', 'Global Scope Applied');
        });
    }
}
