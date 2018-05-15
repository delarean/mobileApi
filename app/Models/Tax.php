<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{

    protected $table = 'taxes_type';

    protected $guarded = ['id'];

    public $timestamps = false;

}
