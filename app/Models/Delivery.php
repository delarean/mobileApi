<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{

    protected $table = 'delivery_method';

    protected $guarded = ['id'];

    public $timestamps = false;

}
