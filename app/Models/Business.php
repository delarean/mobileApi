<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    protected $table = 'bussines_type';

    protected $guarded = ['id'];

    public $timestamps = false;
}
