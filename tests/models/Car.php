<?php

namespace Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Car extends Model
{
    use SoftDeletes;

    protected $connection = 'connection2';

    protected $table = 'cars';

    protected $fillable = ['name'];
}
