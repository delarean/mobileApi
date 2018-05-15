<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderResponse extends Model
{

    protected $guarded = ['id'];

    protected $table = 'orders_response';

    public $timestamps = false;

}
