<?php

namespace Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use SoftDeletes;

    protected $fillable = ['name'];

    public function things()
    {
    	return $this->belongsToMany(Thing::class)
    		->withPivot('deleted_at');
    }
}
