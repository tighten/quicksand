<?php

namespace Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends BaseModel
{
    protected $fillable = ['name'];
}
