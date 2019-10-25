<?php

namespace Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Thing extends Model
{
    use SoftDeletes;

    protected $fillable = ['name'];

    public function people()
    {
    	return $this->belongsToMany(Person::class)
    		->withPivot('deleted_at');
    }
}
